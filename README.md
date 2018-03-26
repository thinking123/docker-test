# Bento-Background-PHP

测试域名: http://api.fising.cn

生产域名: https://api.makebento.com (新接口暂不可用)

### 接口列表

| 接口名称 | 请求方法 | 接口地址 | 请求参数 | 备注 |
| :--- | :--- | :--- | :--- | --- |
| 1. Google 登录 |  POST | /token | source: required | 固定值 'google' | 
|  |  | | id_token: required | Goolg id_token |
| 2. 请求 Magic 登录 |  POST | /magic | email: required | 用户邮箱地址，用于接收 magic link | 
| 3. 确认 Magic 登录 | PUT | /magic | email: required | 用户邮箱地址 |
|  |  | | token: required | magic token |
| 4. Magic 登录 |  POST | /token | source: required | 固定值 'magic' |
|  | |  | token: required| 通过接口 2 取得的 token |
| 5. 刷新 accessToken |  PUT | /token | refresh_token: required | refresh oken |
| 6. 注销登录 |  DELETE | /token/{accessToken} |  |  |
| 7. 获取用户信息 |  GET | /profile|  |  |
| 8. 设备列表 |  GET | /devices|  |  |
| 9. 文件列表 |  GET | /files| offset:optional | 偏移位置, 默认 0 |
|  |   | | limit:optional | 最大限量, 默认 10 |
| 10. 创建文件 |  POST | /file| name:optional | 文件名称 |
| |  | | public:optional | 是否公开(是:true/1否:false/0) |
| 11. 获取文件 | GET | /file/{id}| |  |
| 12. 编辑文件 | PUT | /file/{id}| name:required | 文件名称 |
|  |  | | public:required | 是否公开(是:true/1否:false/0) |
| 13. 删除文件 | DELETE | /file/{id}|  |  |
| 14. 创建 layer | POST | /file\\|component/{id}/layer | name:required | Layer 名称 |
| |  |  | parent:required | 父 Layer id |
| |  |  | before:required | 同级下一层 Layer 的 id, 没有则传 0 |
| |  |  | type:required | Layer 的类型 |
| |  |  | data:optional | JSON 字符串 |
| |  |  | styles:optional | JSON 字符串 |
| 15. 更新 layer | PUT | /layer/{id} | name:required | Layer 名称 |
| |  |  | parent:required | 父 Layer id |
| |  |  | before:required | 同级下一层 Layer 的 id |
| |  |  | data:optional | JSON 字符串 |
| |  |  | styles:optional | JSON 字符串 |
| 16. 删除 layer | DELETE | /layer/{id} |  |  |
| 17. 获取文件或组件顶层 layer | GET | /file\\|component/{id}/layers |  |  |
| 17. 获取 layer 后代 | GET | /layer/{id}/layers |  |  |
| 18. 创建 Team | POST | /team | name:required | 团队名称 |
| 19. 创建组件 |  POST | /file/{id}/component| name:optional | 组件名称 |
| |  | | public:optional | 是否公开(是:true/1否:false/0) |
| 20. 删除组件 | DELETE | /component/{id}|  |  |
| 21. 获取组件 | GET | /component/{id}| |  |
| 22. 组件列表 |  GET | /components | offset:optional | 偏移位置, 默认 0 |
|  |   | | limit:optional | 最大限量, 默认 10 |
| 23. 编辑组件 | PUT | /component/{id}| name:required | 组件名称 |
|  |  | | public:required | 是否公开(是:true/1否:false/0) |
| 24. Layer 与 Component 互转 | POST | /layer/{id}/transform |  | 本接口用于 component 和 layer 的互相转换。确切的说，是 slot layer 与非 slot layer 之间的转换。URL 中的 id 为 layer 的 id。该操作是异步的，返回的数据中 job 为任务 ID，前端需要用该参数调用接口 25 以查询操作是否完成。 |
| 25. 查询 JOB 状态 | GET | /job/{id}| | 该接口用于查询 JOB 完成情况。返回值中的 data[status] 包含五种状态：WAITING/PENDING/FAILED/SUCCESS/UNKNOWN. |
| 26. 查询文件相关组件列表 | GET | /file/{id}/components| | 该接口用于查询文件相关组件列表 |
|  |  | | offset:optional | 偏移位置, 默认 0 |
|  |  | | limit:optional | 最大限量, 默认 10 |
| 27. 创建文件的 Design Token | POST | /file/{id}/designToken| | 该接口用于创建文件相关的 design token |
|  |  | | name:optional | 名称, 默认 Untitled |
|  |  | | value:optional | 值, 默认为空字符串 |
| 28. 查询文件相关 Design Token 列表 | GET | /file/{id}/designTokens | | 该接口用于查询文件相关 design token 列表 |
|  |  | | offset:optional | 偏移位置, 默认 0 |
|  |  | | limit:optional | 最大限量, 默认 10 |
| 29. 更新 Design Token | PUT | /designToken/{id} | | 该接口用于更新 design token |
|  |  | | name:optional | 名称, 默认 Untitled |
|  |  | | value:optional | 值, 默认为空字符串 |
| 30. 删除 Design Token | DELETE | /designToken/{id}|  | 该接口用于删除 Design Token |
| 31. 请求快速登录链接 | POST | /quick |  | 该接口用于获取一个快速登录链接。用户邮箱将收到一个快速登录链接。 |
|  |  |  | email:required | 用户快速登录的用户的邮箱地址 |
| 32. 快速登录 | POST | /token |  | 该接口用于快速登录。需配合接口 31 使用。 |
|  | |  | source: required| 固定值 'quick' |
|  | |  | email: required| 通过接口 31 取得的链接，其 query string 中的 email |
|  | |  | token: required| 通过接口 31 取得的链接，其 query string 中的 token |
| 33. 字体列表 | GET | /fonts |  | 该接口用于获取字体列表。不需要用户认证。 |
| 34. 文件上传 | POST | /storage/write |  | 该接口用于上传文件。该 HTTP 请求的 entity-body 为文件的二进制内容。此外, 需要增加 Content-Type 请求头, 用于声明文件的 MIME 类型。错误的类型声明将会导致文件无法被浏览器正常解析 |
| 35. 创建文件的 Content Token | POST | /file/{id}/contentToken| | 该接口用于创建文件相关的 content token |
|  |  | | name:optional | 名称, 默认 Untitled |
|  |  | | value:optional | 值, 默认为空字符串 |
| 36. 查询文件相关 Content Token 列表 | GET | /file/{id}/contentTokens | | 该接口用于查询文件相关 content token 列表 |
|  |  | | offset:optional | 偏移位置, 默认 0 |
|  |  | | limit:optional | 最大限量, 默认 10 |
| 37. 更新 Content Token | PUT | /contentToken/{id} | | 该接口用于更新 content token |
|  |  | | name:optional | 名称, 默认 Untitled |
|  |  | | value:optional | 值, 默认为空字符串 |
| 38. 删除 Content Token | DELETE | /contentToken/{id}|  | 该接口用于删除 content Token |
| 39. 更新 Team | PUT | /team/{id}|  | 该接口用于编辑 Team(仅 owner 有此权利) |
|  |  | | name:required | Team 名称 |
| 40. 获取 Team 信息 | GET | /team/{id}|  | 该接口用于获取 Team 详细信息 |
| 41. 获取全部可用 icon 信息 | GET | /icons |  | 该接口用于获取全部可用的 icons |
| 42. 创建一个属于个人账户的 icon lib | POST | /iconLib |  | 该接口用于创建一个属于个人账户的 icon lib |
|  |  | | name:required | icon lib 的名称 |
| 43. 更新一个属于个人账户的 icon lib | PUT | /iconLib/{id} |  | 该接口用于更新一个属于个人账户的 icon lib |
|  |  | | name:required | icon lib 的名称 |
| 44. 删除一个属于个人账户的 icon lib | DELETE | /iconLib/{id} |  | 该接口用于删除一个属于个人账户的 icon lib |
| 45. 创建一个 icon | POST | /iconLib/{iconLibId}/icon |  | 该接口用于创建 icon |
|  |  | | name:required | icon 的名称 |
|  |  | | tags:optional | icon 关键字, json 格式. 例如: ["key1", "key2"] |
|  |  | | path:required | icon 的 URL |
| 46. 更新一个 icon | PUT | /icon/{id} |  | 该接口用于更新 icon |
|  |  | | name:required | icon 的名称 |
|  |  | | tags:optional | icon 关键字, json 格式. 例如: ["key1", "key2"] |
|  |  | | path:required | icon 的 URL |
| 47. 删除一个 icon | DELETE | /icon/{id} |  | 该接口用于删除 icon |


### 认证方法

1. 接口 1 ~ 4, 33 不需要认证；
2. 认证方法：请求头添加 "Authorization: Bearer {accessToken}" 

### 参数格式

参数格式支持两种: "application/json" 和 "application/x-www-form-urlencoded"

### 响应格式

成功时:

```json
{
  "status": true,
  "data": {
    // 这里是业务数据
  }
}
```

失败时:

```json
{
  "status": false,
  "code": 10101, // 错误代码
  "message": "Invalid parameter [id_token] value",
  "data": {
    // 请求相关数据  
  }
}
```