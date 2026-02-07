# Steam Profile Query

A Steam profile query system based on Steam Web API, helping users quickly understand their Steam account gaming statistics.

![Steam Profile Query](https://img.shields.io/badge/Steam-Profile%20Query-1b2838?style=flat-square&logo=steam)
![PHP](https://img.shields.io/badge/PHP-8.0+-777bb4?style=flat-square&logo=php)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat-square&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat-square&logo=css3)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&logo=javascript&logoColor=black)

[中文](README.md) | **English**

## ✨ Features

- 🎮 **Game Library Display** - Complete display of user's Steam game library
- ⏱️ **Playtime Analysis** - Show playtime in days/hours format with personalized comments
- 📅 **Account Registration Time** - Precisely calculate account registration days and date
- 🎯 **Gaming Style Analysis** - Analyze user's gaming style based on game genres
- 📱 **Device Detection** - Automatically detect device type and provide adaptation tips
- 🎨 **Ink Wash Style UI** - Elegant ink wash painting style interface design

## 🚀 Quick Start

### Requirements

- PHP 8.0 or higher
- Web server (Apache/Nginx)
- Steam Web API Key

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/2334985103/steam-profile-query.git
   cd steam-profile-query
   ```

2. **Configure API Key**
   
   Configure your Steam Web API Key in the `api.php` file:
   ```php
   $apiKey = 'YOUR_STEAM_API_KEY';
   ```

3. **Deploy to server**
   
   Upload project files to your web server directory.

4. **Access the application**
   
   Visit `http://your-domain.com` in your browser.

## 📖 Usage

1. Enter Steam Friend Code in the input box on the homepage
2. Click the "Query" button
3. View detailed profile analysis report

### Get Steam Friend Code

1. Open Steam client
2. Click your profile in the top right corner
3. Select "Edit Profile"
4. Click the "Account" tab
5. Copy "Steam ID" or "Friend Code"

## 🛠️ Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP
- **API**: Steam Web API
- **Fonts**: Orbitron, Rajdhani, Microsoft YaHei
- **Icons**: Font Awesome

## 📁 Project Structure

```
steam-profile-query/
├── index.html          # Main page
├── about.html          # About page
├── api.php             # Backend API processing
├── script.js           # Frontend scripts
├── style.css           # Stylesheet
├── README.md           # Chinese documentation
└── README_EN.md        # English documentation
```

## 🔧 Core Features

### Playtime Formatting
```php
function formatPlaytimeWithDays($minutes) {
    if ($minutes < 60) return $minutes . ' minutes';
    elseif ($minutes < 1440) {
        $hours = (int)($minutes / 60);
        $mins = $minutes % 60;
        return $hours . ' hours ' . $mins . ' minutes';
    } else {
        $days = (int)($minutes / 1440);
        $totalHours = (int)($minutes / 60);
        return $days . ' days (' . $totalHours . ' hours)';
    }
}
```

### Device Detection
```javascript
function detectDevice() {
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isTablet = /iPad|Android(?!.*Mobile)|Tablet/i.test(navigator.userAgent);
    // Return device type and adaptation tips
}
```

## 🌐 API Interfaces

This project uses the following Steam Web API interfaces:

- `ISteamUser/GetPlayerSummaries` - Get player basic information
- `IPlayerService/GetOwnedGames` - Get owned games list

## 📝 Changelog

### 2026-02-08
- ✨ Initial release
- 🎨 Ink wash painting style interface design
- 📱 Add device detection feature
- 💬 Add smart comment system

## 🤝 Contributing

Issues and Pull Requests are welcome!

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**lhost**

- GitHub: [@2334985103](https://github.com/2334985103/)
- QQ: 2334985103
- WeChat: North10006
- Email: [2334985103@qq.com](mailto:2334985103@qq.com)
- Steam: [Friend Code 1128412874](https://steamcommunity.com/profiles/76561199091658602)

## 🙏 Acknowledgments

- [Steam Web API](https://developer.valvesoftware.com/wiki/Steam_Web_API)
- [Font Awesome](https://fontawesome.com/)
- Hong Kong Server Provider

---

⭐ If this project helps you, please give it a Star!
