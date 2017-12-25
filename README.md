# Bento-Background-PHP

测试域名: http://api.fising.cn

生产域名: https://api.makebento.com (新接口暂不可用)


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
| |  | | public:optional | 是否公开(是1否0) |
| 11. 获取文件 | GET | /file/{id}| |  |
| 12. 编辑文件 | PUT | /file/{id}| name:required | 文件名称 |
|  |  | | public:required | 是否公开(是1否0) |
| 13. 删除文件 | DELETE | /file/{id}|  |  |
| 14. 创建 layer | POST | /file/{id}/layer | name:required | Layer 名称 |
| |  |  | parent:required | 父 Layer id |
| |  |  | before:required | 同级下一层 Layer 的 id |
| |  |  | type:required | Layer 的类型 |
| |  |  | data:optional | JSON 字符串 |
| |  |  | styles:optional | JSON 字符串 |
| 15. 更新 layer | PUT | /layer/{id} | name:required | Layer 名称 |
| |  |  | parent:required | 父 Layer id |
| |  |  | before:required | 同级下一层 Layer 的 id |
| |  |  | data:optional | JSON 字符串 |
| |  |  | styles:optional | JSON 字符串 |
| 16. 删除 layer | DELETE | /layer/{id} |  |  |
| 17. 获取文件顶层 layer | GET | /file/{id}/layers |  |  |
| 17. 获取 layer 后代 | GTE | /layer/{id}/layers |  |  |
| 18. 创建 Team | POST | /team | name:required | 团队名称 |