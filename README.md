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


### 认证方法

1. 接口 1 ~ 4 不需要认证；
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