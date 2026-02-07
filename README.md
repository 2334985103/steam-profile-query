# Steam Profile Query

一个基于 Steam Web API 开发的个人资料查询系统，帮助用户快速了解 Steam 账号的游戏情况。

![Steam Profile Query](https://img.shields.io/badge/Steam-Profile%20Query-1b2838?style=flat-square&logo=steam)
![PHP](https://img.shields.io/badge/PHP-8.0+-777bb4?style=flat-square&logo=php)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat-square&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat-square&logo=css3)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black)

## ✨ 功能特性

- 🎮 **游戏库展示** - 完整展示用户的 Steam 游戏库
- ⏱️ **游戏时长分析** - 以天/小时格式显示游戏时长，并提供个性化评语
- 📅 **注册时间计算** - 精确计算账号注册天数和注册日期
- 🎯 **游戏风格分析** - 基于游戏类型分析用户的游戏风格
- 📱 **设备检测** - 自动检测设备类型并提供适配提示
- 🎨 **水墨风格界面** - 优雅的水墨画风格 UI 设计

## 🚀 快速开始

### 环境要求

- PHP 8.0 或更高版本
- Web 服务器（Apache/Nginx）
- Steam Web API Key

### 安装步骤

1. **克隆仓库**
   ```bash
   git clone https://github.com/2334985103/steam-profile-query.git
   cd steam-profile-query
   ```

2. **配置 API Key**
   
   在 `api.php` 文件中配置你的 Steam Web API Key：
   ```php
   $apiKey = 'YOUR_STEAM_API_KEY';
   ```

3. **部署到服务器**
   
   将项目文件上传到你的 Web 服务器目录。

4. **访问应用**
   
   在浏览器中访问 `http://your-domain.com`

## 📖 使用说明

1. 在首页输入框中输入 Steam 好友代码
2. 点击"查询"按钮
3. 查看详细的个人资料分析报告

### 获取 Steam 好友代码

1. 打开 Steam 客户端
2. 点击右上角个人资料
3. 选择"编辑个人资料"
4. 点击"账号"选项卡
5. 复制"Steam ID"或"好友代码"

## 🛠️ 技术栈

- **前端**: HTML5, CSS3, JavaScript
- **后端**: PHP
- **API**: Steam Web API
- **字体**: Orbitron, Rajdhani, Microsoft YaHei
- **图标**: Font Awesome

## 📁 项目结构

```
steam-profile-query/
├── index.html          # 主页面
├── about.html          # 关于页面
├── api.php             # 后端 API 处理
├── script.js           # 前端脚本
├── style.css           # 样式文件
└── README.md           # 项目说明
```

## 🔧 核心功能实现

### 游戏时长格式化
```php
function formatPlaytimeWithDays($minutes) {
    if ($minutes < 60) return $minutes . ' 分钟';
    elseif ($minutes < 1440) {
        $hours = (int)($minutes / 60);
        $mins = $minutes % 60;
        return $hours . ' 小时 ' . $mins . ' 分钟';
    } else {
        $days = (int)($minutes / 1440);
        $totalHours = (int)($minutes / 60);
        return $days . ' 天 (' . $totalHours . ' 小时)';
    }
}
```

### 设备检测
```javascript
function detectDevice() {
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isTablet = /iPad|Android(?!.*Mobile)|Tablet/i.test(navigator.userAgent);
    // 返回设备类型和适配提示
}
```

## 🌐 API 接口

本项目使用以下 Steam Web API 接口：

- `ISteamUser/GetPlayerSummaries` - 获取玩家基本信息
- `IPlayerService/GetOwnedGames` - 获取已拥有游戏列表

## 📝 更新日志

### 2026-02-08
- ✨ 初始版本发布
- 🎨 水墨画风格界面设计
- 📱 添加设备检测功能
- 💬 添加智能评语系统

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📄 许可证

本项目采用 MIT 许可证 - 详见 [LICENSE](LICENSE) 文件

## 👨‍💻 作者

**lhost**

- GitHub: [@2334985103](https://github.com/2334985103/)
- QQ: 2334985103
- 微信: North10006
- 邮箱: [2334985103@qq.com](mailto:2334985103@qq.com)
- Steam: [好友代码 1128412874](https://steamcommunity.com/profiles/76561199088678602)

## 🙏 致谢

- [Steam Web API](https://developer.valvesoftware.com/wiki/Steam_Web_API)
- [Font Awesome](https://fontawesome.com/)
- 香港服务器提供商

---

⭐ 如果这个项目对你有帮助，欢迎给个 Star！
