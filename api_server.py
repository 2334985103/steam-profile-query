#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Steam 查询 API 服务器
使用 Python 替代 PHP
"""

import http.server
import socketserver
import json
import urllib.request
import urllib.error
import ssl
import re
from datetime import datetime, timedelta

PORT = 8080

# 默认 API Key（需要用户自己申请并替换）
DEFAULT_API_KEY = 'YOUR_DEFAULT_API_KEY_HERE'

class SteamAPIHandler(http.server.SimpleHTTPRequestHandler):
    def end_headers(self):
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type')
        super().end_headers()

    def do_OPTIONS(self):
        self.send_response(200)
        self.end_headers()

    def do_POST(self):
        if self.path == '/api.php':
            self.handle_api_request()
        else:
            self.send_error(404)

    def do_GET(self):
        # 提供静态文件
        if self.path == '/':
            self.path = '/index.html'
        return super().do_GET()

    def handle_api_request(self):
        content_length = int(self.headers.get('Content-Length', 0))
        post_data = self.rfile.read(content_length).decode('utf-8')
        
        try:
            data = json.loads(post_data)
            friend_code = data.get('friendCode', '').strip()
            api_key = DEFAULT_API_KEY  # 只使用后端配置的 API Key
        except json.JSONDecodeError:
            self.send_error_response(400, '无效的请求数据')
            return

        # 验证输入
        if not friend_code:
            self.send_error_response(400, '请输入好友代码')
            return

        if not re.match(r'^\d+$', friend_code):
            self.send_error_response(400, '好友代码格式不正确，请输入纯数字')
            return

        # 转换好友代码为 Steam ID64
        steam_id64 = self.convert_friend_code_to_steam_id(friend_code)
        if not steam_id64:
            self.send_error_response(400, '无法转换好友代码，请检查输入是否正确')
            return

        # 检查 API Key
        if api_key == 'YOUR_DEFAULT_API_KEY_HERE' or not api_key:
            self.send_error_response(400, '请配置 Steam API Key', 
                '您需要申请自己的 Steam API Key 才能使用此服务。请访问 https://steamcommunity.com/dev/apikey 申请。')
            return

        # 获取玩家信息
        player_info = self.get_player_info(steam_id64, api_key)
        if not player_info:
            self.send_error_response(404, '未找到该用户的信息，请检查好友代码是否正确')
            return

        # 获取游戏列表
        games_list = self.get_player_games(steam_id64, api_key)

        # 计算账号注册时间
        account_creation = self.calculate_account_creation_date(steam_id64)

        # 构建响应
        response = {
            'success': True,
            'player': {
                'steamId': steam_id64,
                'personaName': player_info.get('personaname', 'Unknown'),
                'profileUrl': player_info.get('profileurl', ''),
                'avatar': player_info.get('avatarfull', ''),
                'avatarMedium': player_info.get('avatarmedium', ''),
                'avatarSmall': player_info.get('avatar', ''),
                'personaState': player_info.get('personastate', 0),
                'communityVisibilityState': player_info.get('communityvisibilitystate', 0),
                'profileState': player_info.get('profilestate', 0),
                'lastLogoff': player_info.get('lastlogoff', 0),
                'commentPermission': player_info.get('commentpermission', 0),
                'realName': player_info.get('realname', ''),
                'primaryClanId': player_info.get('primaryclanid', ''),
                'timeCreated': player_info.get('timecreated', 0),
                'gameId': player_info.get('gameid', ''),
                'gameServerIp': player_info.get('gameserverip', ''),
                'gameExtraInfo': player_info.get('gameextrainfo', ''),
                'cityId': player_info.get('cityid', 0),
                'locCountryCode': player_info.get('loccountrycode', ''),
                'locStateCode': player_info.get('locstatecode', ''),
                'locCityId': player_info.get('loccityid', 0),
            },
            'account': account_creation,
            'games': {
                'totalCount': games_list.get('game_count', 0),
                'totalPlaytime': 0,
                'totalPlaytimeHours': 0,
                'list': []
            }
        }

        # 处理游戏列表
        if 'games' in games_list and isinstance(games_list['games'], list):
            total_playtime = 0
            games = []

            for game in games_list['games']:
                playtime_minutes = game.get('playtime_forever', 0)
                playtime_hours = round(playtime_minutes / 60, 1)
                total_playtime += playtime_minutes

                app_id = game.get('appid', 0)
                icon_url = f"https://steamcdn-a.akamaihd.net/steamcommunity/public/images/apps/{app_id}/{game.get('img_icon_url', '')}.jpg"
                logo_url = ''
                if game.get('img_logo_url'):
                    logo_url = f"https://steamcdn-a.akamaihd.net/steamcommunity/public/images/apps/{app_id}/{game.get('img_logo_url')}.jpg"

                games.append({
                    'appId': app_id,
                    'name': game.get('name', 'Unknown Game'),
                    'playtime': playtime_minutes,
                    'playtimeHours': playtime_hours,
                    'playtimeText': self.format_playtime(playtime_minutes),
                    'iconUrl': icon_url,
                    'logoUrl': logo_url,
                    'hasCommunityVisibleStats': game.get('has_community_visible_stats', False),
                    'playtimeWindows': game.get('playtime_windows_forever', 0),
                    'playtimeMac': game.get('playtime_mac_forever', 0),
                    'playtimeLinux': game.get('playtime_linux_forever', 0),
                    'rtimeLastPlayed': game.get('rtime_last_played', 0)
                })

            # 按游戏时长排序
            games.sort(key=lambda x: x['playtime'], reverse=True)

            response['games']['totalPlaytime'] = total_playtime
            response['games']['totalPlaytimeHours'] = round(total_playtime / 60, 1)
            response['games']['totalPlaytimeText'] = self.format_playtime(total_playtime)
            response['games']['list'] = games

        # 添加在线状态
        response['player']['personaStateText'] = self.get_persona_state_text(response['player']['personaState'])
        response['player']['personaStateColor'] = self.get_persona_state_color(response['player']['personaState'])

        self.send_json_response(200, response)

    def send_json_response(self, status_code, data):
        self.send_response(status_code)
        self.send_header('Content-Type', 'application/json; charset=utf-8')
        self.end_headers()
        self.wfile.write(json.dumps(data, ensure_ascii=False).encode('utf-8'))

    def send_error_response(self, status_code, error, message=None):
        data = {'error': error}
        if message:
            data['message'] = message
        self.send_json_response(status_code, data)

    def convert_friend_code_to_steam_id(self, friend_code):
        """转换好友代码为 Steam ID64"""
        # 如果已经是 Steam ID64 (17位数字)
        if len(friend_code) == 17 and friend_code > '76561197960265728':
            return friend_code

        # 好友代码转 Steam ID64
        base = 76561197960265728
        try:
            return str(base + int(friend_code))
        except ValueError:
            return None

    def make_request(self, url):
        """发起 HTTP 请求"""
        try:
            ctx = ssl.create_default_context()
            ctx.check_hostname = False
            ctx.verify_mode = ssl.CERT_NONE
            
            req = urllib.request.Request(url, headers={'User-Agent': 'SteamQuery/1.0'})
            with urllib.request.urlopen(req, context=ctx, timeout=30) as response:
                return json.loads(response.read().decode('utf-8'))
        except Exception as e:
            print(f"Request error: {e}")
            return None

    def get_player_info(self, steam_id64, api_key):
        """获取玩家信息"""
        url = f"https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={api_key}&steamids={steam_id64}"
        response = self.make_request(url)
        if response and 'response' in response and 'players' in response['response']:
            players = response['response']['players']
            if players:
                return players[0]
        return None

    def get_player_games(self, steam_id64, api_key):
        """获取玩家游戏列表"""
        url = f"https://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key={api_key}&steamid={steam_id64}&format=json&include_appinfo=1&include_played_free_games=1"
        response = self.make_request(url)
        if response and 'response' in response:
            return response['response']
        return {'game_count': 0, 'games': []}

    def calculate_account_creation_date(self, steam_id64):
        """计算账号注册时间"""
        base_steam_id = 76561197960265728
        
        try:
            account_id = int(steam_id64) - base_steam_id
        except ValueError:
            account_id = 0

        # Steam 发布日期
        steam_launch_date = datetime(2003, 9, 12)
        
        # 估算注册时间
        estimated_seconds = account_id // 10
        estimated_date = steam_launch_date + timedelta(seconds=estimated_seconds)
        
        # 如果估算时间在未来，使用当前时间
        if estimated_date > datetime.now():
            estimated_date = datetime.now() - timedelta(days=365)

        now = datetime.now()
        age = now - estimated_date
        age_days = age.days
        age_years = age_days // 365
        remaining_days = age_days % 365

        if age_years > 0:
            age_text = f"{age_years} 年"
            if remaining_days > 30:
                months = remaining_days // 30
                age_text += f" {months} 个月"
        else:
            months = age_days // 30
            if months > 0:
                age_text = f"{months} 个月"
            else:
                age_text = f"{age_days} 天"

        return {
            'date': estimated_date.strftime('%Y-%m-%d'),
            'timestamp': int(estimated_date.timestamp()),
            'age': age_days,
            'ageText': age_text
        }

    def format_playtime(self, minutes):
        """格式化游戏时长"""
        if minutes < 60:
            return f"{minutes} 分钟"
        elif minutes < 1440:
            hours = minutes // 60
            mins = minutes % 60
            if mins > 0:
                return f"{hours} 小时 {mins} 分钟"
            return f"{hours} 小时"
        else:
            days = minutes // 1440
            hours = (minutes % 1440) // 60
            if hours > 0:
                return f"{days} 天 {hours} 小时"
            return f"{days} 天"

    def get_persona_state_text(self, state):
        """获取在线状态文本"""
        states = {
            0: '离线',
            1: '在线',
            2: '忙碌',
            3: '离开',
            4: ' snooze',
            5: 'looking to trade',
            6: 'looking to play'
        }
        return states.get(state, '未知')

    def get_persona_state_color(self, state):
        """获取在线状态颜色"""
        colors = {
            0: 'offline',
            1: 'online',
            2: 'busy',
            3: 'away',
            4: 'away',
            5: 'online',
            6: 'online'
        }
        return colors.get(state, 'offline')


if __name__ == '__main__':
    import os
    os.chdir(os.path.dirname(os.path.abspath(__file__)))
    
    with socketserver.TCPServer(("", PORT), SteamAPIHandler) as httpd:
        print(f"Steam 查询服务器运行在 http://localhost:{PORT}")
        print("按 Ctrl+C 停止服务器")
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print("\n服务器已停止")
