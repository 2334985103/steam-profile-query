<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 在这里配置你的 Steam API Key
$DEFAULT_API_KEY = '1913B21988D947F4DD06A722E5E850BB';

// 获取请求参数
$input = json_decode(file_get_contents('php://input'), true);
$friendCode = isset($input['friendCode']) ? trim($input['friendCode']) : '';

// 验证输入
if (empty($friendCode)) {
    http_response_code(400);
    echo json_encode(['error' => '请输入好友代码']);
    exit;
}

// 验证好友代码格式
if (!preg_match('/^\d+$/', $friendCode)) {
    http_response_code(400);
    echo json_encode(['error' => '好友代码格式不正确，请输入纯数字']);
    exit;
}

// 检查 API Key
if ($DEFAULT_API_KEY === 'YOUR_STEAM_API_KEY_HERE' || empty($DEFAULT_API_KEY)) {
    http_response_code(400);
    echo json_encode([
        'error' => '请配置 Steam API Key',
        'message' => '请在 api.php 文件中配置您的 Steam API Key。访问 https://steamcommunity.com/dev/apikey 申请。'
    ]);
    exit;
}

// 转换好友代码为 Steam ID64
$steamId64 = convertFriendCodeToSteamId($friendCode);

if (!$steamId64) {
    http_response_code(400);
    echo json_encode(['error' => '无法转换好友代码，请检查输入是否正确']);
    exit;
}

// 获取玩家信息
$playerInfo = getPlayerInfo($steamId64, $DEFAULT_API_KEY);
if (!$playerInfo) {
    http_response_code(404);
    echo json_encode(['error' => '未找到该用户的信息，请检查好友代码是否正确']);
    exit;
}

// 获取游戏列表
$gamesList = getPlayerGames($steamId64, $DEFAULT_API_KEY);

// 计算账号注册时间（使用 Steam API 返回的 timecreated）
$accountCreation = calculateAccountCreationDate($playerInfo['timecreated'] ?? 0);

// 构建响应
$response = [
    'success' => true,
    'player' => [
        'steamId' => $steamId64,
        'personaName' => $playerInfo['personaname'] ?? 'Unknown',
        'profileUrl' => $playerInfo['profileurl'] ?? '',
        'avatar' => $playerInfo['avatarfull'] ?? '',
        'avatarMedium' => $playerInfo['avatarmedium'] ?? '',
        'avatarSmall' => $playerInfo['avatar'] ?? '',
        'personaState' => $playerInfo['personastate'] ?? 0,
        'communityVisibilityState' => $playerInfo['communityvisibilitystate'] ?? 0,
        'profileState' => $playerInfo['profilestate'] ?? 0,
        'lastLogoff' => $playerInfo['lastlogoff'] ?? 0,
        'commentPermission' => $playerInfo['commentpermission'] ?? 0,
        'realName' => $playerInfo['realname'] ?? '',
        'primaryClanId' => $playerInfo['primaryclanid'] ?? '',
        'timeCreated' => $playerInfo['timecreated'] ?? 0,
        'gameId' => $playerInfo['gameid'] ?? '',
        'gameServerIp' => $playerInfo['gameserverip'] ?? '',
        'gameExtraInfo' => $playerInfo['gameextrainfo'] ?? '',
        'cityId' => $playerInfo['cityid'] ?? 0,
        'locCountryCode' => $playerInfo['loccountrycode'] ?? '',
        'locStateCode' => $playerInfo['locstatecode'] ?? '',
        'locCityId' => $playerInfo['loccityid'] ?? 0,
    ],
    'account' => $accountCreation,
    'games' => [
        'totalCount' => $gamesList['game_count'] ?? 0,
        'totalPlaytime' => 0,
        'totalPlaytimeHours' => 0,
        'list' => []
    ]
];

// 处理游戏列表
if (isset($gamesList['games']) && is_array($gamesList['games'])) {
    $totalPlaytime = 0;
    $games = [];
    $gameGenres = [];
    
    foreach ($gamesList['games'] as $game) {
        $playtimeMinutes = $game['playtime_forever'] ?? 0;
        $playtimeHours = round($playtimeMinutes / 60, 1);
        $totalPlaytime += $playtimeMinutes;
        
        $appId = $game['appid'] ?? 0;
        $gameName = $game['name'] ?? 'Unknown Game';
        $iconUrl = "https://steamcdn-a.akamaihd.net/steamcommunity/public/images/apps/{$appId}/" . ($game['img_icon_url'] ?? '') . ".jpg";
        $logoUrl = '';
        if (!empty($game['img_logo_url'])) {
            $logoUrl = "https://steamcdn-a.akamaihd.net/steamcommunity/public/images/apps/{$appId}/{$game['img_logo_url']}.jpg";
        }
        
        // 分析游戏类型
        $genre = analyzeGameGenre($gameName);
        if ($genre) {
            $gameGenres[$genre] = ($gameGenres[$genre] ?? 0) + $playtimeMinutes;
        }
        
        $games[] = [
            'appId' => $appId,
            'name' => $gameName,
            'playtime' => $playtimeMinutes,
            'playtimeHours' => $playtimeHours,
            'playtimeText' => formatPlaytimeWithDays($playtimeMinutes),
            'playtimeDays' => round($playtimeMinutes / 1440, 1),
            'iconUrl' => $iconUrl,
            'logoUrl' => $logoUrl,
            'hasCommunityVisibleStats' => $game['has_community_visible_stats'] ?? false,
            'playtimeWindows' => $game['playtime_windows_forever'] ?? 0,
            'playtimeMac' => $game['playtime_mac_forever'] ?? 0,
            'playtimeLinux' => $game['playtime_linux_forever'] ?? 0,
            'rtimeLastPlayed' => $game['rtime_last_played'] ?? 0
        ];
    }
    
    // 按游戏时长排序
    usort($games, function($a, $b) {
        return $b['playtime'] - $a['playtime'];
    });
    
    $response['games']['totalPlaytime'] = $totalPlaytime;
    $response['games']['totalPlaytimeHours'] = round($totalPlaytime / 60, 1);
    $response['games']['totalPlaytimeDays'] = round($totalPlaytime / 1440, 1);
    $response['games']['totalPlaytimeText'] = formatPlaytimeWithDays($totalPlaytime);
    $response['games']['playtimeComment'] = getTotalPlaytimeComment($totalPlaytime);
    $response['games']['gamingStyle'] = analyzeGamingStyle($gameGenres, $totalPlaytime);
    $response['games']['list'] = $games;
}

// 添加在线状态文本和颜色
$response['player']['personaStateText'] = getPersonaStateText($response['player']['personaState']);
$response['player']['personaStateColor'] = getPersonaStateColor($response['player']['personaState']);

echo json_encode($response, JSON_UNESCAPED_UNICODE);

// ==================== 辅助函数 ====================

function convertFriendCodeToSteamId($friendCode) {
    // 如果已经是 Steam ID64 (17位数字)
    if (strlen($friendCode) === 17 && $friendCode > '76561197960265728') {
        return $friendCode;
    }
    
    // 好友代码转 Steam ID64
    $base = '76561197960265728';
    
    // 使用 BCMath 处理大数
    if (function_exists('bcadd')) {
        return bcadd($friendCode, $base);
    } else {
        // 手动计算大数相加
        return addLargeNumbers($friendCode, $base);
    }
}

function addLargeNumbers($a, $b) {
    $a = strrev($a);
    $b = strrev($b);
    $result = '';
    $carry = 0;
    $maxLen = max(strlen($a), strlen($b));
    
    for ($i = 0; $i < $maxLen; $i++) {
        $digitA = isset($a[$i]) ? (int)$a[$i] : 0;
        $digitB = isset($b[$i]) ? (int)$b[$i] : 0;
        $sum = $digitA + $digitB + $carry;
        $result .= ($sum % 10);
        $carry = (int)($sum / 10);
    }
    
    if ($carry > 0) {
        $result .= $carry;
    }
    
    return strrev($result);
}

function getPlayerInfo($steamId64, $apiKey) {
    $url = "https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$apiKey}&steamids={$steamId64}";
    
    $response = makeRequest($url);
    if ($response && isset($response['response']['players'][0])) {
        return $response['response']['players'][0];
    }
    
    return null;
}

function getPlayerGames($steamId64, $apiKey) {
    $url = "https://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key={$apiKey}&steamid={$steamId64}&format=json&include_appinfo=1&include_played_free_games=1";
    
    $response = makeRequest($url);
    if ($response && isset($response['response'])) {
        return $response['response'];
    }
    
    return ['game_count' => 0, 'games' => []];
}

function makeRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SteamQuery/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        return json_decode($response, true);
    }
    
    return null;
}

function calculateAccountCreationDate($timeCreated) {
    // 如果没有 timecreated，返回未知
    if (empty($timeCreated) || $timeCreated == 0) {
        return [
            'date' => '未知',
            'timestamp' => 0,
            'age' => 0,
            'ageText' => '未知',
            'comment' => '无法获取注册时间信息'
        ];
    }
    
    $now = time();
    $age = $now - $timeCreated;
    $ageDays = (int)($age / 86400);
    $ageYears = (int)($ageDays / 365);
    $remainingDays = $ageDays % 365;
    
    $ageText = '';
    if ($ageYears > 0) {
        $ageText = "{$ageYears} 年";
        if ($remainingDays > 30) {
            $months = (int)($remainingDays / 30);
            $ageText .= " {$months} 个月";
        }
    } else {
        $months = (int)($ageDays / 30);
        if ($months > 0) {
            $ageText = "{$months} 个月";
        } else {
            $ageText = "{$ageDays} 天";
        }
    }
    
    // 根据账号年龄生成评语
    $comment = '';
    if ($ageYears >= 10) {
        $comments = [
            '十年以上的老玩家！Steam 的忠实用户！🏅',
            '骨灰级玩家！见证了 Steam 的发展历程！📜',
            '十年账号， priceless！💎',
            '老玩家认证！你的游戏库一定很精彩！🎮'
        ];
    } elseif ($ageYears >= 5) {
        $comments = [
            '五年以上的资深玩家！👑',
            '你的 Steam 账号已经成年了！🎂',
            '资深用户！游戏品味一定很棒！⭐',
            '五年时光，游戏陪伴！🌟'
        ];
    } elseif ($ageYears >= 2) {
        $comments = [
            '两年以上的玩家！已经找到自己喜欢的游戏类型了吧？🎯',
            '稳步成长的游戏爱好者！📈',
            '两年时光，游戏世界的大门已为你敞开！🚪',
            '不错的游戏历程，继续探索吧！🔍'
        ];
    } elseif ($ageYears >= 1) {
        $comments = [
            '一年以上的玩家！已经度过新手期了！💪',
            'Steam 用户满一年！游戏之旅渐入佳境！🎮',
            '一年的游戏时光，收获满满！🎁',
            '已经是个合格的 Steam 用户了！👍'
        ];
    } else {
        $comments = [
            'Steam 新手！欢迎加入这个大家庭！👋',
            '刚开始的 Steam 之旅，精彩游戏等你发现！✨',
            '新用户！建议从经典游戏开始探索！🗺️',
            '欢迎来到 Steam 世界！🎉'
        ];
    }
    $comment = $comments[array_rand($comments)];
    
    return [
        'date' => date('Y-m-d', $timeCreated),
        'timestamp' => $timeCreated,
        'age' => $ageDays,
        'ageText' => $ageText,
        'comment' => $comment
    ];
}

// 格式化时长（同时显示天数和小时，格式：X 天 (Y 小时)）
function formatPlaytimeWithDays($minutes) {
    if ($minutes < 60) {
        return $minutes . ' 分钟';
    } elseif ($minutes < 1440) {
        $hours = (int)($minutes / 60);
        $mins = $minutes % 60;
        if ($mins > 0) {
            return $hours . ' 小时 ' . $mins . ' 分钟';
        }
        return $hours . ' 小时';
    } else {
        // 超过1天，同时显示天数和小时，格式：X 天 (Y 小时)
        $days = (int)($minutes / 1440);
        $totalHours = (int)($minutes / 60);
        
        return $days . ' 天 (' . $totalHours . ' 小时)';
    }
}

// 获取总游戏时长评语
function getTotalPlaytimeComment($totalMinutes) {
    $days = $totalMinutes / 1440;
    $hours = $totalMinutes / 60;
    
    if ($days >= 365) {
        $comments = [
            '哇塞！你已经花了超过一年的时间在游戏上！这是要申请吉尼斯纪录吗？🎮',
            '一年以上的游戏时长... 你是住在游戏里的吗？🏠',
            '真正的硬核玩家！你的 dedication 令人敬佩！💪',
            '这已经是一份全职工作了！考虑开个直播吗？📺'
        ];
    } elseif ($days >= 180) {
        $comments = [
            '半年以上的游戏时光！你是真正的游戏爱好者！🌟',
            '哇！这时长足够从新手变成职业选手了！🏆',
            '半年的时间都在游戏里，你的生活平衡还好吗？😄',
            '这游戏时长... 你的 Steam 账号值钱了！💎'
        ];
    } elseif ($days >= 90) {
        $comments = [
            '三个月的游戏时长！你对游戏是真爱啊！❤️',
            '这已经超过了大多数人的游戏时长了！👍',
            '三个月... 你在这个虚拟世界里建立帝国了吗？🏰',
            '资深玩家认证！继续加油！🚀'
        ];
    } elseif ($days >= 30) {
        $comments = [
            '一个月的游戏时长！不错的开始！👌',
            '你已经是个合格的游戏玩家了！🎮',
            '这时间足够通关很多3A大作了！🎯',
            '游戏已经成为你生活的一部分了吧？😊'
        ];
    } elseif ($days >= 7) {
        $comments = [
            '一周以上的游戏时间！继续保持！💪',
            '你的游戏之旅才刚刚开始！🌟',
            '不错的游戏时长，找到你喜欢的游戏了吗？🎲',
            '休闲玩家的完美时长！享受游戏吧！🎉'
        ];
    } elseif ($hours >= 24) {
        $comments = [
            '已经花了一整天在游戏上了！🕐',
            '新手玩家正在成长中！📈',
            '开始探索游戏世界了吗？🗺️',
            '不错的开始，还有更多游戏等你发现！🔍'
        ];
    } else {
        $comments = [
            '游戏新手！还有很多精彩等你探索！✨',
            '刚开始的游戏之旅，慢慢享受吧！🌱',
            '你的游戏故事才刚刚开始书写！📖',
            '轻度玩家， quality over quantity！👌'
        ];
    }
    
    return $comments[array_rand($comments)];
}

// 获取单个游戏评语
function getGameComment($minutes, $gameName) {
    // 如果游戏时长为0，返回特定评语
    if ($minutes <= 0) {
        return '还没开始玩呢，快试试吧！🎮';
    }
    
    $days = $minutes / 1440;
    $hours = $minutes / 60;
    
    // 特定游戏评语
    $specificComments = [
        'Dota 2' => [
            '100+ 小时' => '已经开始理解这个游戏了！🧠',
            '500+ 小时' => '你是真的爱这个游戏！💕',
            '1000+ 小时' => '传奇玩家！你的天梯分一定很高！🏆'
        ],
        'Counter-Strike' => [
            '100+ 小时' => '爆头率提升中！🎯',
            '500+ 小时' => '老兵了！记得休息眼睛！👀',
            '1000+ 小时' => '职业选手预备役！🥇'
        ],
        'PUBG' => [
            '100+ 小时' => '吃鸡次数应该不少了吧？🍗',
            '500+ 小时' => '跳伞专家！🪂',
            '1000+ 小时' => '绝地求生大师！🏆'
        ],
        'Grand Theft Auto V' => [
            '100+ 小时' => '洛圣都的街头霸王！🚗',
            '500+ 小时' => '你已经比当地人还了解这座城市！🌆',
            '1000+ 小时' => '真正的犯罪大师！😎'
        ]
    ];
    
    // 检查特定游戏
    foreach ($specificComments as $game => $comments) {
        if (stripos($gameName, $game) !== false) {
            if ($days >= 42 && isset($comments['1000+ 小时'])) {
                return $comments['1000+ 小时'];
            } elseif ($days >= 21 && isset($comments['500+ 小时'])) {
                return $comments['500+ 小时'];
            } elseif ($days >= 4 && isset($comments['100+ 小时'])) {
                return $comments['100+ 小时'];
            }
        }
    }
    
    // 通用评语 - 根据时长返回不同评语
    if ($days >= 30) {
        return '这款游戏是你的真爱！投入了大量时间！💎';
    } elseif ($days >= 14) {
        return '两周以上的时间！你是这款游戏的忠实粉丝！⭐';
    } elseif ($days >= 7) {
        return '一周的游戏时光！相当不错的投入！🎮';
    } elseif ($hours >= 24) {
        return '一整天都在玩这个！看来很对你的胃口！😄';
    } elseif ($hours >= 10) {
        return '已经开始上头了！继续探索吧！🚀';
    } elseif ($hours >= 2) {
        return '初步体验完成，感觉如何？🤔';
    } elseif ($minutes >= 30) {
        return '刚开始接触，给这款游戏一个机会吧！✨';
    } else {
        return '刚开始玩，还在探索阶段！🔍';
    }
}

function getPersonaStateText($state) {
    $states = [
        0 => '离线',
        1 => '在线',
        2 => '忙碌',
        3 => '离开',
        4 => 'snooze',
        5 => 'looking to trade',
        6 => 'looking to play'
    ];
    
    return $states[$state] ?? '未知';
}

function getPersonaStateColor($state) {
    $colors = [
        0 => 'offline',
        1 => 'online',
        2 => 'busy',
        3 => 'away',
        4 => 'away',
        5 => 'online',
        6 => 'online'
    ];
    
    return $colors[$state] ?? 'offline';
}

// 分析游戏类型
function analyzeGameGenre($gameName) {
    $genres = [
        'FPS' => ['Counter-Strike', 'CS', 'Valorant', 'Overwatch', 'Call of Duty', 'Battlefield', 'Apex Legends', 'PUBG', 'Rainbow Six', 'Team Fortress'],
        'MOBA' => ['Dota 2', 'League of Legends', 'LOL', 'Heroes of the Storm', 'Smite'],
        'RPG' => ['The Witcher', 'Elder Scrolls', 'Skyrim', 'Fallout', 'Mass Effect', 'Dragon Age', 'Dark Souls', 'Elden Ring', 'Final Fantasy'],
        'MMORPG' => ['World of Warcraft', 'WOW', 'Guild Wars', 'Final Fantasy XIV', 'Black Desert', 'Genshin Impact'],
        'Battle Royale' => ['PUBG', 'Fortnite', 'Apex Legends', 'Call of Duty: Warzone'],
        'Strategy' => ['Civilization', 'Total War', 'StarCraft', 'Age of Empires', 'Crusader Kings', 'Europa Universalis'],
        'Sandbox' => ['Minecraft', 'Terraria', 'Starbound', 'Factorio', 'Satisfactory'],
        'Racing' => ['Forza', 'Need for Speed', 'Gran Turismo', 'F1', 'Assetto Corsa'],
        'Sports' => ['FIFA', 'NBA', 'eFootball', 'Football Manager'],
        'Horror' => ['Resident Evil', 'Silent Hill', 'Dead Space', 'Outlast', 'Amnesia'],
        'Indie' => ['Hades', 'Celeste', 'Hollow Knight', 'Stardew Valley', 'Undertale'],
        'Action' => ['Grand Theft Auto', 'GTA', 'Red Dead Redemption', 'Assassin\'s Creed', 'Watch Dogs'],
        'Adventure' => ['Uncharted', 'Tomb Raider', 'Life is Strange', 'The Walking Dead']
    ];
    
    foreach ($genres as $genre => $keywords) {
        foreach ($keywords as $keyword) {
            if (stripos($gameName, $keyword) !== false) {
                return $genre;
            }
        }
    }
    
    return 'Other';
}

// 分析游戏风格
function analyzeGamingStyle($gameGenres, $totalPlaytime) {
    if (empty($gameGenres) || $totalPlaytime <= 0) {
        return '你的游戏库还在建设中，期待发现你的游戏风格！🎮';
    }
    
    // 找出主要游戏类型
    arsort($gameGenres);
    $topGenre = array_key_first($gameGenres);
    $topGenreTime = $gameGenres[$topGenre];
    $topGenrePercentage = ($topGenreTime / $totalPlaytime) * 100;
    
    $styles = [
        'FPS' => [
            'high' => '你是天生的神枪手！FPS 游戏占据了你的大部分时间，反应速度和精准度一定是你的强项！🎯',
            'medium' => '看来你喜欢快节奏的射击游戏，享受枪林弹雨中的刺激感！🔫',
            'low' => '偶尔来几局射击游戏放松，你的游戏口味很均衡！⚖️'
        ],
        'MOBA' => [
            'high' => '策略大师！你在 MOBA 游戏中投入了大量时间，团队协作和战术思维是你的强项！🏆',
            'medium' => '享受 MOBA 带来的竞技乐趣，每局都是新的挑战！⚔️',
            'low' => '偶尔打几局 MOBA，轻松娱乐为主！😊'
        ],
        'RPG' => [
            'high' => '沉浸式玩家！你热爱 RPG 的丰富剧情和角色成长，每个游戏都是一段传奇旅程！📖',
            'medium' => '喜欢沉浸在游戏世界中，体验不同的人生故事！🌟',
            'low' => '偶尔体验 RPG 的精彩剧情，享受慢节奏的游戏时光！☕'
        ],
        'MMORPG' => [
            'high' => '虚拟世界居民！你在 MMORPG 中建立了第二个家，社交和冒险是你游戏生活的核心！🌍',
            'medium' => '享受 MMORPG 的社交乐趣，和朋友一起冒险是最棒的！👥',
            'low' => '偶尔登录 MMORPG 看看，保持与游戏世界的联系！🔗'
        ],
        'Battle Royale' => [
            'high' => '生存专家！你在 Battle Royale 游戏中磨练出了极强的生存本能和战术意识！🏆',
            'medium' => '享受大逃杀的紧张刺激，每局都是全新的冒险！🪂',
            'low' => '偶尔来一局大逃杀，体验心跳加速的感觉！💓'
        ],
        'Strategy' => [
            'high' => '战略大师！你热爱思考和规划，策略游戏是你展现智慧的舞台！🧠',
            'medium' => '享受策略游戏带来的智力挑战，每一步都深思熟虑！♟️',
            'low' => '偶尔玩玩策略游戏，锻炼一下大脑！🤔'
        ],
        'Sandbox' => [
            'high' => '创造大师！你在沙盒游戏中释放了无限创意，建造了属于自己的世界！🏗️',
            'medium' => '喜欢沙盒游戏的自由度，随心所欲地创造和探索！🔨',
            'low' => '偶尔在沙盒游戏中放松一下，享受创造的乐趣！✨'
        ],
        'Racing' => [
            'high' => '速度狂人！你对赛车游戏的热爱让你的反应速度达到了极致！🏎️',
            'medium' => '享受速度与激情的碰撞，每场比赛都是挑战！🏁',
            'low' => '偶尔来几圈赛车，感受速度的快感！💨'
        ],
        'Sports' => [
            'high' => '体育达人！你在体育游戏中展现了出色的运动天赋和战术理解！⚽',
            'medium' => '热爱体育游戏，享受竞技的乐趣！🏀',
            'low' => '偶尔玩玩体育游戏，保持运动精神！🏃'
        ],
        'Horror' => [
            'high' => '恐怖游戏勇士！你的胆量令人佩服，越是恐怖越要挑战！👻',
            'medium' => '喜欢恐怖游戏带来的刺激感，享受心跳加速的时刻！😱',
            'low' => '偶尔挑战恐怖游戏，测试一下自己的胆量！🎃'
        ],
        'Indie' => [
            'high' => '独立游戏鉴赏家！你善于发现小众精品，品味独特！💎',
            'medium' => '喜欢探索独立游戏的创意世界，支持小众开发者！🌟',
            'low' => '偶尔尝试独立游戏，发现不一样的游戏体验！🔍'
        ],
        'Action' => [
            'high' => '动作游戏大师！你在动作游戏中展现了出色的操作技巧和反应速度！💪',
            'medium' => '享受动作游戏带来的爽快战斗体验！⚔️',
            'low' => '偶尔玩玩动作游戏，释放一下压力！💥'
        ],
        'Adventure' => [
            'high' => '冒险家！你热爱探索未知的世界，每个游戏都是新的冒险！🗺️',
            'medium' => '喜欢冒险游戏的探索元素，享受发现秘密的乐趣！🔍',
            'low' => '偶尔来场冒险，体验不同的游戏世界！🌄'
        ],
        'Other' => [
            'high' => '多元化玩家！你的游戏品味非常广泛，各种类型的游戏都能享受！🎮',
            'medium' => '游戏口味多样，不拘泥于特定类型！🌈',
            'low' => '还在探索中，寻找最适合自己的游戏类型！🔍'
        ]
    ];
    
    $level = $topGenrePercentage >= 40 ? 'high' : ($topGenrePercentage >= 20 ? 'medium' : 'low');
    
    return $styles[$topGenre][$level] ?? $styles['Other'][$level];
}
