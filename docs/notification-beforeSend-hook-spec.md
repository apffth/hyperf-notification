# `apffth/hyperf-notification`：新增 `beforeSend()` 生命週期钩子

> 本文档是一份可直接执行的实作规格（Implementation Spec），交付对象：`apffth/hyperf-notification` 套件仓库的 AI 编程 agent。
> 目标仓库：https://github.com/apffth/hyperf-notification
> 涉及档案：`src/Notification.php`、`src/NotificationSender.php`（其余档案不需改动）

## 1. 背景与问题

目前 `Notification` 基类只提供以下生命週期钩子：

- `shouldQueue($notifiable): bool` —— 是否应该进队列（`Queueable` trait）
- `shouldSend($notifiable): bool` —— 是否应该发送（`Queueable` trait）
- `via($notifiable): array` —— 决定发送渠道
- `toXxx($notifiable)` —— 各渠道内容表示（`toMail`/`toSms`/`toDatabase`/...）
- `afterSend($response, $channel, $notifiable): void` —— 单一渠道发送后回调（每渠道触发一次）
- `afterChannelsSend($notifiable): void` —— `final`，遍历所有已发送渠道并逐一调用 `afterSend`（框架内部调用）

**缺口**：没有一个"发送前、只执行一次"的数据准备钩子。导致下游使用者只能：

1. 把重逻辑（DB 查询、外部 RPC、短链生成等）写进建构函数 —— 这些逻辑会在 `notify()` 呼叫的当下（即呼叫方协程）同步执行，即使 `shouldQueue()` 回传 `true`，因为 `NotificationSender::send()` 是先完整建构好 `Notification` 物件、再把物件塞进队列 Job，建构函数的副作用早已同步跑完。
2. 或者自行在 `toXxx()` 方法里手写「是否已准备」标志位并在每个 `toXxx()` 开头重复调用，写法啰嗦且容易遗漏。
3. 或者滥用 `shouldSend()` 顶替「数据准备」职责，混淆了它原本「是否允许发送」的布林判断语义。

真实案例：下游服务把短链生成（一次跨服务 RPC 调用）放在 Notification 建构函数里，某次短链服务 DNS 解析超时，直接让呼叫方的同步 gRPC 请求整体失败——即使该请求对应的业务逻辑早已提交成功，只是「附带」的简讯通知失败，也拖垮了整个请求。

## 2. 目标

- 新增 `beforeSend()` 生命週期钩子：**仅在通知即将真正发送前（`via()`/`toXxx()` 之前）调用一次**，无论同步发送还是异步队列（`NotificationJob`）路径皆生效。
- 幂等保护由框架托管，下游通知类不需要自己维护 `resolved`/`prepared` 标志位。
- 与 `afterSend()` 对称命名，符合套件既有的 `shouldQueue`/`shouldSend`/`afterSend` 命名风格，便于发现与理解。
- 完全向后兼容：默认空实现，现有下游通知类无需任何修改即可继续运作。

## 3. 非目标

- 不改变 `notify()` 的呼叫方式（不引入延迟建构/Closure 工厂等破坏性设计）。
- 不改变 `shouldSend()` / `shouldQueue()` 的既有语义。
- 不新增per-channel 的「发送前」钩子（`beforeSend()` 是整个通知只触发一次，不随渠道数量重复，这点需要在 PHPDoc 中明确标注，避免与 `afterSend()` 的「逐渠道触发」语义混淆）。

## 4. API 设计

### 4.1 `src/Notification.php`

新增一个私有状态属性 + 两个方法（一个可覆写的 protected 钩子 + 一个 final 的框架调用入口）。

**位置建议**：紧接在 `protected array $channelResponses = [];` 之后新增状态属性；两个新方法建议放在 `via()` 宣告之后、`toArray()` 之前（作为「发送前」生命週期钩子的分组），或者放在 `afterChannelsSend()`/`afterSend()` 附近（作为「发送后」对称分组）。两种位置皆可，以下 diff 选择放在 `via()` 之后。

```diff
     protected array $channelResponses = [];
 
+    /**
+     * 标记 beforeSend() 是否已执行，用于保证幂等（仅执行一次）。
+     */
+    private bool $prepared = false;
+
     /**
      * 获取通知应该发送的渠道。
      * @param mixed $notifiable
      */
     abstract public function via($notifiable): array;
 
+    /**
+     * 发送前的数据准备钩子（可覆盖）。
+     *
+     * 仅在通知即将真正发送前（via()/toXxx() 之前）调用一次，且无论走同步发送
+     * 或异步队列（NotificationJob）皆保证只执行一次。适合把原本放在建构函数里、
+     * 且依赖外部资源（DB 查询、RPC 调用、短链生成等）的重逻辑搬移至此，
+     * 避免这些副作用在呼叫方（例如同步 RPC 请求协程）中被同步执行。
+     *
+     * 注意：此钩子与 afterSend() 不同，不会随渠道数量重复调用，
+     * 整个发送过程（不论最终发送几个渠道）仅触发一次。
+     *
+     * @param mixed $notifiable
+     */
+    protected function beforeSend(mixed $notifiable): void
+    {
+        // 用户可以在通知类中覆盖此方法
+    }
+
+    /**
+     * 框架内部调用入口，确保 beforeSend() 只执行一次。
+     * 此方法是 final 的，以确保其幂等保护逻辑不被意外覆盖。
+     *
+     * @param mixed $notifiable
+     */
+    final public function beforeSendOnce(mixed $notifiable): void
+    {
+        if ($this->prepared) {
+            return;
+        }
+
+        $this->beforeSend($notifiable);
+
+        $this->prepared = true;
+    }
+
     /**
      * 获取通知的数组表示。
      * @param mixed $notifiable
      * @return array
      */
     public function toArray($notifiable)
```

### 4.2 `src/NotificationSender.php`

在 `sendNow()` 中，`setId()` 之后、计算 `via($notifiable)` 之前插入呼叫：

```diff
     public function sendNow($notifiable, Notification $notification): void
     {
         $notification->setId();
+        $notification->beforeSendOnce($notifiable);
 
         $channels = collect($notification->via($notifiable))
             ->filter(fn ($channel) => ! in_array($channel, $notification->sentChannels))
             ->all();
```

> 放在 `via()` 之前的原因：部分下游通知类的 `via()` 逻辑也可能依赖「准备好的数据」（例如依内容动态决定要不要发某个渠道），因此准备工作必须在 `via()` 之前完成。

`NotificationJob.php` **不需要修改**——它透过 `NotificationSender::sendNow()` 间接触发 `beforeSendOnce()`，且它原有的 `try/catch` 已经能正确捕获 `beforeSend()` 抛出的例外（见第 5 节）。

## 5. 执行时序与边界情况

### 5.1 呼叫时序（同步路径）

```
Notifiable::notify()
 → NotificationSender::send()
    → shouldQueue() === false
    → shouldSend() === true
    → sendNow()
       → setId()
       → beforeSendOnce()  ← 新增，在此同步执行一次
       → via()
       → 逐渠道 toXxx() + channel->send()
       → afterChannelsSend() → afterSend()（逐渠道）
```

### 5.2 呼叫时序（异步队列路径，主要受益场景）

```
Notifiable::notify()
 → NotificationSender::send()
    → shouldQueue() === true
    → queueNotification() → dispatch(NotificationJob) 立即返回，呼叫方协程结束
       ...（队列消费协程）...
       → NotificationJob::handle()
          → shouldSend() === true
          → NotificationSender::sendNow()
             → setId()
             → beforeSendOnce()  ← 新增，在队列消费协程执行，不阻塞呼叫方
             → via()
             → 逐渠道 toXxx() + channel->send()
             → afterChannelsSend() → afterSend()（逐渠道）
```

**这正是本次新增钩子要解决的核心场景**：`beforeSend()` 里的重逻辑（含外部 RPC）被推迟到队列消费协程执行，呼叫方（例如同步 gRPC handler）在 `dispatch()` 后立即返回，不再被下游服务的网路延迟/超时拖累。

### 5.3 例外传播与重试

- 若 `beforeSend()` 抛出例外：
  - **队列路径**：例外从 `sendNow()` 冒泡至 `NotificationJob::handle()` 的 `try/catch`，触发 `$this->notification->failed($e)`，再重新抛出，交由 `Hyperf\AsyncQueue` 依照 `tries()`/`delay()` 设定重试。因为例外发生在 `$this->prepared = true;` 赋值**之前**，`prepared` 标志不会被错误地标记为已完成，下次重试（新的反序列化物件实例，`prepared` 预设为 `false`）会重新执行 `beforeSend()`。
  - **同步路径**：例外直接冒泡给呼叫 `notify()` 的呼叫方，与目前 `via()`/`toXxx()` 抛例外时的既有行为一致，属于预期内、无需额外处理的行为。
- **与逐渠道 try/catch 的隔离粒度差异**：目前 `sendNow()` 内对每个 channel 各自 `try/catch`，一个渠道失败不影响其他渠道；而 `beforeSendOnce()` 在渠道回圈**外层**执行，一旦抛错会导致该次发送的**所有渠道全部**不会执行（连 `via()` 都不会被呼叫到）。这是合理的（数据都没准备好，各渠道大概率都发不出去），但**必须在 README/CHANGELOG 中明确说明**这一行为差异，避免使用者误以为和 channel 级隔离一致。

### 5.4 序列化相容性

`NotificationJob` 透过 `Hyperf\AsyncQueue` 序列化后写入 Redis，新增的 `private bool $prepared = false;` 属性会随物件一併序列化，属于单纯的标量属性，不引入任何相容性问题。

## 6. 向后兼容性

- `beforeSend()` 默认空实现，`beforeSendOnce()` 为新增的 `final` 方法，两者均不影响任何现有下游通知类的行为。
- 不修改任何既有方法签名/既有属性型别，属于纯新增（Additive Change）。
- 建议版本号：**v1.3.0**（新增向后兼容的 Feature，符合 SemVer minor 版本规则）。

## 7. 测试要求

请在套件既有测试目录（`test/`，若无则新建 `test/NotificationBeforeSendTest.php` 或等效位置）补齐以下测试案例：

1. **仅执行一次**：构造一个会在 `beforeSend()` 中递增计数器的测试用 Notification 子类，透过 `NotificationSender::sendNow()` 呼叫一次，断言计数器为 `1`；再手动呼叫 `beforeSendOnce()` 第二次，断言计数器仍为 `1`（幂等）。
2. **执行顺序**：断言 `beforeSend()` 于 `via()` 之前被呼叫（可用呼叫顺序记录阵列 + 断言阵列内容为 `['beforeSend', 'via', 'toXxx', ...]`）。
3. **向后兼容**：对一个**未覆写** `beforeSend()` 的既有测试 Notification 类执行完整发送流程，确认行为与新增此功能前完全一致（跑一遍既有测试套件，全部应继续通过，不需新增断言）。
4. **队列路径下的例外与重试语义**：在 `beforeSend()` 中抛出例外，断言：
   - `NotificationJob::handle()` 会捕获该例外并呼叫 `Notification::failed()`；
   - 例外会被重新抛出（交由外层队列重试机制处理）；
   - 新建的 Job 实例（模拟重试）中 `prepared` 状态为初始值 `false`（即并未被错误标记为已完成）。
5. **同步路径下的例外传播**：`shouldQueue()` 回传 `false` 且 `beforeSend()` 抛出例外时，例外应直接从 `NotificationSender::send()` 冒泡给呼叫方（不应被静默吞掉）。

## 8. 文件更新要求

- `README.md`：新增「发送前数据准备（`beforeSend`）」章节，至少包含：
  - 使用场景说明（何时该用 `beforeSend()` 而不是建构函数）。
  - 一个完整的 Before/After 对比范例（例如把短链生成逻辑从建构函数搬到 `beforeSend()`）。
  - 明确标注：「此钩子整个发送流程仅触发一次，不随渠道数量重复」，并提醒与逐渠道的 `afterSend()` 语义不同。
  - 明确标注：「`beforeSend()` 抛出例外会导致本次发送的所有渠道皆不执行」。
- `CHANGELOG.md`（若存在）：新增 v1.3.0 条目，描述新增 `beforeSend()`/`beforeSendOnce()` 钩子。

## 9. 验收标准（Checklist）

- [ ] `src/Notification.php` 新增 `$prepared` 属性、`beforeSend()`、`beforeSendOnce()`，程式码与第 4.1 节 diff 一致。
- [ ] `src/NotificationSender.php` 的 `sendNow()` 在 `setId()` 之后、`via()` 之前新增 `$notification->beforeSendOnce($notifiable);` 呼叫。
- [ ] `src/NotificationJob.php` 无需改动（确认既有 `try/catch` 已可正确处理新钩子抛出的例外）。
- [ ] 第 7 节列出的 5 项测试全部新增并通过。
- [ ] 既有测试套件（`composer test` 或等效指令）全部通过，无回归。
- [ ] 静态分析（`composer analyse` / PHPStan，若套件已配置）无新增错误。
- [ ] `README.md` / `CHANGELOG.md` 依第 8 节完成更新。
- [ ] 版本号依 SemVer 规则更新为 v1.3.0（或维护者指定的下一个 minor 版本）。
