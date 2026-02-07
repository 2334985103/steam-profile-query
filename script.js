// Steam Profile Query - Cyberpunk Edition

document.addEventListener('DOMContentLoaded', function() {
    // DOM 元素
    const loadingScreen = document.getElementById('loadingScreen');
    const app = document.getElementById('app');
    const searchPage = document.getElementById('searchPage');
    const queryingPage = document.getElementById('queryingPage');
    const resultPage = document.getElementById('resultPage');
    const friendCodeInput = document.getElementById('friendCode');
    const searchBtn = document.getElementById('searchBtn');
    const backBtn = document.getElementById('backBtn');
    const errorMessage = document.getElementById('errorMessage');
    const copyBtn = document.getElementById('copyBtn');
    
    // 检测设备类型
    detectDevice();
    
    // 加载页面动画
    initLoadingScreen();
    
    // 状态
    let currentData = null;
    let currentSort = 'playtime';
    let typingInterval = null;
    
    // 检测设备类型
    function detectDevice() {
        const deviceNotice = document.getElementById('deviceNotice');
        const deviceIcon = document.getElementById('deviceIcon');
        const deviceText = document.getElementById('deviceText');
        
        // 检测是否为移动设备
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        const isTablet = /iPad|Android(?!.*Mobile)|Tablet/i.test(navigator.userAgent);
        
        if (isTablet) {
            deviceIcon.className = 'fas fa-tablet-alt';
            deviceText.textContent = '你当前使用平板进行访问，建议使用电脑访问获得最佳体验';
            deviceNotice.classList.add('mobile');
        } else if (isMobile) {
            deviceIcon.className = 'fas fa-mobile-alt';
            deviceText.textContent = '你当前使用手机进行访问，建议使用电脑访问获得最佳体验';
            deviceNotice.classList.add('mobile');
        } else {
            deviceIcon.className = 'fas fa-desktop';
            deviceText.textContent = '你当前使用电脑进行访问，获得最佳体验';
            deviceNotice.classList.add('desktop');
        }
        
        // 3秒后自动隐藏提示
        setTimeout(() => {
            deviceNotice.style.opacity = '0';
            deviceNotice.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                deviceNotice.style.display = 'none';
            }, 500);
        }, 5000);
    }

    // 初始化加载页面
    function initLoadingScreen() {
        const particles = document.getElementById('particles');
        
        // 创建粒子
        for (let i = 0; i < 30; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 10 + 's';
            particle.style.animationDuration = (10 + Math.random() * 10) + 's';
            particles.appendChild(particle);
        }
        
        // 2.5秒后显示主应用
        setTimeout(() => {
            loadingScreen.style.opacity = '0';
            loadingScreen.style.transition = 'opacity 0.5s';
            
            setTimeout(() => {
                loadingScreen.style.display = 'none';
                app.style.display = 'block';
                app.style.animation = 'fadeIn 0.5s';
                friendCodeInput.focus();
            }, 500);
        }, 2500);
    }
    
    // 事件监听
    searchBtn.addEventListener('click', handleSearch);
    backBtn.addEventListener('click', showSearchPage);
    copyBtn.addEventListener('click', copySteamId);
    
    friendCodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') handleSearch();
    });
    
    // 排序按钮
    document.querySelectorAll('.sort-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const sortType = this.dataset.sort;
            if (sortType !== currentSort) {
                currentSort = sortType;
                updateSortButtons();
                if (currentData) renderGamesList(currentData.games.list);
            }
        });
    });
    
    // 搜索处理
    async function handleSearch() {
        const friendCode = friendCodeInput.value.trim();
        
        if (!friendCode) {
            showError('请输入 Steam 好友代码');
            return;
        }
        
        if (!/^\d+$/.test(friendCode)) {
            showError('好友代码格式不正确，请输入纯数字');
            return;
        }
        
        hideError();
        showQueryingPage();
        
        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ friendCode: friendCode })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || data.message || '查询失败');
            }
            
            if (data.success) {
                currentData = data;
                // 等待查询动画完成
                setTimeout(() => {
                    renderResults(data);
                    showResultPage();
                }, 2000);
            } else {
                throw new Error(data.error || '查询失败');
            }
        } catch (error) {
            showError(error.message);
            showSearchPage();
        }
    }
    
    // 显示查询中页面
    function showQueryingPage() {
        searchPage.style.display = 'none';
        queryingPage.style.display = 'flex';
        resultPage.style.display = 'none';
        
        // 开始打字机效果
        startTypingEffect();
        
        // 步骤动画
        animateSteps();
    }
    
    // 打字机效果
    function startTypingEffect() {
        const texts = [
            '正在连接 Steam 服务器...',
            '正在验证用户身份...',
            '正在获取游戏数据...',
            '正在分析档案信息...',
            '即将完成...'
        ];
        let textIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        const typingText = document.getElementById('typingText');
        
        if (typingInterval) clearInterval(typingInterval);
        
        typingInterval = setInterval(() => {
            const currentText = texts[textIndex];
            
            if (isDeleting) {
                typingText.textContent = currentText.substring(0, charIndex - 1);
                charIndex--;
            } else {
                typingText.textContent = currentText.substring(0, charIndex + 1);
                charIndex++;
            }
            
            if (!isDeleting && charIndex === currentText.length) {
                isDeleting = true;
                setTimeout(() => {}, 1000);
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                textIndex = (textIndex + 1) % texts.length;
            }
        }, 100);
    }
    
    // 步骤动画
    function animateSteps() {
        const steps = document.querySelectorAll('.step');
        steps.forEach((step, index) => {
            step.classList.remove('active', 'completed');
            setTimeout(() => {
                step.classList.add('active');
                if (index > 0) {
                    steps[index - 1].classList.remove('active');
                    steps[index - 1].classList.add('completed');
                }
            }, index * 600);
        });
    }
    
    // 渲染结果
    function renderResults(data) {
        console.log('API Response:', data);
        const player = data.player;
        const account = data.account;
        const games = data.games;
        console.log('Account:', account);
        console.log('Games:', games);
        
        // 头像 - 使用多个备用源
        const avatar = document.getElementById('avatar');
        const defaultAvatar = 'data:image/svg+xml,%3Csvg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 184 184\"%3E%3Crect fill=\"%231b2838\" width=\"184\" height=\"184\"/%3E%3Ctext fill=\"%2366c0f4\" font-family=\"Arial\" font-size=\"80\" x=\"50%25\" y=\"50%25\" text-anchor=\"middle\" dominant-baseline=\"middle\"%3E%3F%3C/text%3E%3C/svg%3E';
        
        // 尝试使用 Steam 头像，如果失败则使用默认头像
        if (player.avatar && player.avatar.startsWith('http')) {
            // 尝试替换 CDN 域名以提高可用性
            let avatarUrl = player.avatar;
            // 将 steamcdn-a.akamaihd.net 替换为其他可用域名
            avatarUrl = avatarUrl.replace('steamcdn-a.akamaihd.net', 'avatars.steamstatic.com');
            avatar.src = avatarUrl;
            
            // 加载失败时使用默认头像
            avatar.onerror = function() {
                this.src = defaultAvatar;
                this.onerror = null;
            };
        } else {
            avatar.src = defaultAvatar;
        }
        
        // 状态环
        const statusRing = document.getElementById('statusRing');
        statusRing.className = 'status-ring ' + player.personaStateColor;
        
        // 状态徽章
        const statusBadge = document.getElementById('statusBadge');
        const statusDot = statusBadge.querySelector('.status-dot');
        const statusText = statusBadge.querySelector('.status-text');
        statusDot.className = 'status-dot ' + player.personaStateColor;
        statusText.textContent = player.personaStateText;
        
        // 用户名
        document.getElementById('profileName').textContent = player.personaName;
        
        // Steam ID
        document.getElementById('steamId').textContent = player.steamId;
        
        // 当前游戏
        const currentGame = document.getElementById('currentGame');
        if (player.gameExtraInfo) {
            currentGame.textContent = '🎮 正在玩: ' + player.gameExtraInfo;
        } else {
            currentGame.textContent = '';
        }
        
        // 统计数据
        document.getElementById('gameCount').textContent = games.totalCount.toLocaleString();
        document.getElementById('totalPlaytime').textContent = games.totalPlaytimeText || '0 小时';
        document.getElementById('registerDate').textContent = account.date || '未知';
        document.getElementById('accountAge').textContent = account.ageText || '未知';
        
        // 显示评语
        console.log('playtimeComment:', games.playtimeComment);
        console.log('gamingStyle:', games.gamingStyle);
        console.log('account.comment:', account.comment);
        
        const playtimeComment = document.getElementById('playtimeComment');
        if (games.playtimeComment) {
            playtimeComment.querySelector('span').textContent = games.playtimeComment;
            playtimeComment.style.display = 'flex';
        } else {
            playtimeComment.style.display = 'none';
        }
        
        // 游戏风格评语
        const styleComment = document.getElementById('styleComment');
        if (games.gamingStyle) {
            styleComment.querySelector('span').textContent = games.gamingStyle;
            styleComment.style.display = 'flex';
        } else {
            styleComment.style.display = 'none';
        }
        
        const accountComment = document.getElementById('accountComment');
        if (account.comment) {
            accountComment.querySelector('span').textContent = account.comment;
            accountComment.style.display = 'flex';
        } else {
            accountComment.style.display = 'none';
        }
        
        // 动画填充统计条
        setTimeout(() => {
            document.querySelectorAll('.stat-fill').forEach((fill, index) => {
                fill.style.width = '100%';
            });
        }, 300);
        
        // 渲染游戏列表
        renderGamesList(games.list);
    }
    
    // 渲染游戏列表
    function renderGamesList(games) {
        const gamesList = document.getElementById('gamesList');
        gamesList.innerHTML = '';
        
        if (!games || games.length === 0) {
            gamesList.innerHTML = '<div class="game-item"><div class="game-info"><div class="game-name">暂无游戏数据</div></div></div>';
            return;
        }
        
        // 排序
        let sortedGames = [...games];
        if (currentSort === 'name') {
            sortedGames.sort((a, b) => a.name.localeCompare(b.name, 'zh-CN'));
        }
        
        const maxPlaytime = Math.max(...sortedGames.map(g => g.playtime));
        const displayGames = sortedGames.slice(0, 50);
        
        displayGames.forEach((game, index) => {
            const gameItem = document.createElement('div');
            gameItem.className = 'game-item';
            gameItem.style.animation = `fadeInUp 0.3s ${index * 0.05}s both`;
            
            const progressPercent = maxPlaytime > 0 ? (game.playtime / maxPlaytime) * 100 : 0;
            
            gameItem.innerHTML = `
                <img src="${game.iconUrl}" alt="${game.name}" class="game-icon" 
                     onerror="this.src='https://store.steampowered.com/favicon.ico'">
                <div class="game-info">
                    <div class="game-name">${escapeHtml(game.name)}</div>
                    <div class="game-playtime">${game.playtimeText}</div>
                    <div class="game-bar">
                        <div class="game-bar-fill" style="width: 0%"></div>
                    </div>
                </div>
            `;
            
            gamesList.appendChild(gameItem);
            
            // 动画显示进度条
            setTimeout(() => {
                const fill = gameItem.querySelector('.game-bar-fill');
                if (fill) fill.style.width = progressPercent + '%';
            }, 100 + index * 50);
        });
        
        if (sortedGames.length > 50) {
            const moreItem = document.createElement('div');
            moreItem.className = 'game-item';
            moreItem.style.justifyContent = 'center';
            moreItem.innerHTML = `<div class="game-playtime">还有 ${sortedGames.length - 50} 款游戏...</div>`;
            gamesList.appendChild(moreItem);
        }
    }
    
    // 更新排序按钮
    function updateSortButtons() {
        document.querySelectorAll('.sort-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.sort === currentSort);
        });
    }
    
    // 显示搜索页面
    function showSearchPage() {
        searchPage.style.display = 'flex';
        queryingPage.style.display = 'none';
        resultPage.style.display = 'none';
        
        if (typingInterval) {
            clearInterval(typingInterval);
            typingInterval = null;
        }
        
        // 重置统计条
        document.querySelectorAll('.stat-fill').forEach(fill => {
            fill.style.width = '0%';
        });
        
        friendCodeInput.focus();
    }
    
    // 显示结果页面
    function showResultPage() {
        searchPage.style.display = 'none';
        queryingPage.style.display = 'none';
        resultPage.style.display = 'block';
        
        if (typingInterval) {
            clearInterval(typingInterval);
            typingInterval = null;
        }
        
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    // 显示错误
    function showError(message) {
        errorMessage.querySelector('span').textContent = message;
        errorMessage.classList.add('show');
        setTimeout(hideError, 5000);
    }
    
    // 隐藏错误
    function hideError() {
        errorMessage.classList.remove('show');
    }
    
    // 复制 Steam ID
    function copySteamId() {
        const steamId = document.getElementById('steamId').textContent;
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(steamId).then(showCopyFeedback);
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = steamId;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showCopyFeedback();
        }
    }
    
    // 复制成功反馈
    function showCopyFeedback() {
        const originalHTML = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="fas fa-check"></i>';
        copyBtn.style.color = 'var(--accent)';
        
        setTimeout(() => {
            copyBtn.innerHTML = originalHTML;
            copyBtn.style.color = '';
        }, 2000);
    }
    
    // HTML 转义
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});

// CSS 动画
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);
