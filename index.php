<?php
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Asia/Shanghai');
$website_title = "2hu 自助更新 - 新月杀神祈服务器";
$page_title = "新月杀神祈服务器 2hu 自助更新";
$cooldown = 43200;
$lock_file = '/tmp/2hu_lock.txt';
$counter_file = '/tmp/2hu_counter.txt';

// 初始化访问计数器
function updateVisitCounter() {
    global $counter_file;
    
    $count = 1;
    if (file_exists($counter_file)) {
        $count = (int)file_get_contents($counter_file);
        $count++;
    }
    
    file_put_contents($counter_file, $count);
    return $count;
}

$visit_count = updateVisitCounter();

// 将秒数转换为小时、分钟、秒的格式（带空格）
function formatCooldown($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    
    $parts = [];
    if ($hours > 0) {
        $parts[] = $hours . ' 小时';
    }
    if ($minutes > 0) {
        $parts[] = $minutes . ' 分钟';
    }
    if ($seconds > 0 || empty($parts)) {
        $parts[] = $seconds . ' 秒';
    }
    
    return implode(' ', $parts);
}

// 初始化变量
$message_type = 'success';
$message_content = '';
$should_execute = false;
$remaining = 0;
$retry_time = '';

// 检查频率限制
$last_time = file_exists($lock_file) ? filemtime($lock_file) : 0;
$remaining = max(0, $cooldown - (time() - $last_time));

if ($remaining > 0) {
    // 仍在冷却期，显示警告
    $message_type = 'warning';
    $retry_time = date('Y-m-d H:i:s', time() + $remaining);
    $message_content = "
        <p>刚刚已经更新过了。<span id=\"countdown\" data-seconds=\"$remaining\" style=\"display:none\"></span><span id=\"formatted-countdown\">" . formatCooldown($remaining) . "</span>后可以再次更新。</p>
    ";
} else {
    // 可以执行更新
    touch($lock_file);
    shell_exec('sudo /usr/local/bin/2hu.sh > /dev/null 2>&1');
    $remaining = $cooldown;
    $retry_time = date('Y-m-d H:i:s', time() + $remaining);
    $message_content = "
        <p>已经更新。<span id=\"countdown\" data-seconds=\"$cooldown\" style=\"display:none\"></span><span id=\"formatted-countdown\">" . formatCooldown($cooldown) . "</span>后可以再次更新。</p>
    ";
}

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($website_title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #5c6bc0;
            --primary-light: #8e99f3;
            --primary-dark: #26418f;
            --secondary-color: #26a69a;
            --success-color: #66bb6a;
            --warning-color: #ffa726;
            --error-color: #ef5350;
            --text-primary: #2d3748;
            --text-secondary: #4a5568;
            --bg-color: #f7fafc;
            --card-bg: #ffffff;
            --border-radius: 12px;
            --box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans SC', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--bg-color);
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 40px auto;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 10px;
            line-height: 1.3;
            word-break: keep-all;
            white-space: pre-wrap;
        }

        .header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 25px;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .message {
            padding: 20px;
            border-left: 4px solid;
            border-radius: 4px;
            margin-bottom: 15px;
            background-color: rgba(255, 255, 255, 0.9);
        }

        .success {
            border-color: var(--success-color);
            background-color: rgba(102, 187, 106, 0.1);
        }

        .warning {
            border-color: var(--warning-color);
            background-color: rgba(255, 167, 38, 0.1);
        }

        .info {
            border-color: var(--primary-light);
            background-color: rgba(142, 153, 243, 0.1);
        }

        .message h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .message h3::before {
            content: "";
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 10px;
            background-size: contain;
            background-repeat: no-repeat;
        }

        .success h3::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%2366bb6a' viewBox='0 0 24 24'%3E%3Cpath d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z'/%3E%3C/svg%3E");
        }

        .warning h3::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23ffa726' viewBox='0 0 24 24'%3E%3Cpath d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z'/%3E%3C/svg%3E");
        }

        .info h3::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%238e99f3' viewBox='0 0 24 24'%3E%3Cpath d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z'/%3E%3C/svg%3E");
        }

        .countdown {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary-dark);
        }

        .time-display {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .time-display span {
            font-weight: 500;
            color: var(--text-primary);
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .visit-count {
            font-weight: bold;
            color: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
                padding: 0 15px;
            }
            
            h1 {
                font-size: 1.8rem;
            }
            
            .card {
                padding: 20px;
            }
            
            .time-display {
                flex-direction: column;
                gap: 5px;
            }
        }

        /* 2hu小图标 */
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%; /* 确保圆形显示 */
            background-image: url("http://47.115.41.110/fk.jpg");
            background-size: cover; /* 保持图片比例并填满容器 */
            background-position: center; /* 图片居中显示 */
            background-repeat: no-repeat;
            border: 3px solid var(--primary-light); /* 添加圆形边框 */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* 添加轻微阴影 */
            object-fit: cover; /* 确保图片不变形 */
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo" aria-hidden="true"></div>
            <h1><?php 
                $formatted_title = str_replace('2hu', '​2hu', $page_title);
                echo htmlspecialchars($formatted_title, ENT_QUOTES, 'UTF-8'); 
            ?></h1>
            <p id="greeting-text"><span class="visit-count"><?php echo $visit_count; ?></span></p>
        </div>
        
        <div class="card">
            <div class="message <?php echo $message_type; ?>">
                <h3><?php echo $message_type === 'success' ? '更新成功' : '更新冷却中……'; ?></h3>
                <?php echo $message_content; ?>
                <div class="time-display" style="margin: 15px -10px 0 -10px;">
                    <div style="padding: 0 10px;">上次更新：<span id="last-update-time"><?php echo file_exists($lock_file) ? date('Y-m-d H:i:s', filemtime($lock_file)) : '从未'; ?></span></div>
                    <div style="padding: 0 10px;">当前时间：<span id="current-time"><?php echo date('Y-m-d H:i:s'); ?></span></div>
                    <div style="padding: 0 10px;">下次可更新：<span id="retry-time"><?php echo $retry_time; ?></span></div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="message info">
                <h3>使用说明</h3>
                <p>访问此页面（<a href="#" id="currentLink">47.115.41.110/2hu</a>）即可自助更新 2hu。</p>
                <p>冷却时间：<span class="countdown"><?php echo formatCooldown($cooldown); ?></span></p>
            </div>
        </div>
        
        <div class="footer">
            <p><a href="https://github.com/baisebaoma">baisebaoma</a> &copy; <?php echo date('Y'); ?> - 新月杀神祈服务器</p>
        </div>
    </div>

    <script>
        // 获得当前地址
        document.getElementById('currentLink').href = window.location.href;

        // 更新当前时间
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.getFullYear() + '-' + 
                String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                String(now.getDate()).padStart(2, '0') + ' ' + 
                String(now.getHours()).padStart(2, '0') + ':' + 
                String(now.getMinutes()).padStart(2, '0') + ':' + 
                String(now.getSeconds()).padStart(2, '0');
            document.getElementById('current-time').textContent = timeString;
        }

        // 更新倒计时
    function updateCountdown() {
        const countdownElement = document.getElementById('countdown');
        const formattedElement = document.getElementById('formatted-countdown');
        
        if (!countdownElement || !formattedElement) return;

        let seconds = parseInt(countdownElement.dataset.seconds);
        if (seconds > 0) {
            seconds--;
            countdownElement.dataset.seconds = seconds;
            
            // 更新格式化后的倒计时显示
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;

            let formatted = '';
            if (hours > 0) formatted += hours + ' 小时 ';
            if (minutes > 0) formatted += minutes + ' 分钟 ';
            if (secs > 0 || formatted === '') formatted += secs + ' 秒';

            formattedElement.textContent = formatted.trim();
            
            // 更新重试时间
            const now = new Date();
            now.setSeconds(now.getSeconds() + seconds);
            const retryTimeString = now.getFullYear() + '-' + 
                String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                String(now.getDate()).padStart(2, '0') + ' ' + 
                String(now.getHours()).padStart(2, '0') + ':' + 
                String(now.getMinutes()).padStart(2, '0') + ':' + 
                String(now.getSeconds()).padStart(2, '0');
            document.getElementById('retry-time').textContent = retryTimeString;
        }
    }

        // 根据用户本地时间显示问候语
        function updateGreeting() {
            function getGreeting() {
                const hour = new Date().getHours();
                
                if (hour >= 5 && hour < 8) {
                    return "早上好！";
                } else if (hour >= 8 && hour < 11) {
                    return "上午好！";
                } else if (hour >= 11 && hour < 13) {
                    return "中午好！";
                } else if (hour >= 13 && hour < 18) {
                    return "下午好！";
                } else if (hour >= 18 && hour < 23) {
                    return "晚上好！";
                } else {
                    return "夜深了，注意休息……";
                }
            }
            
            const greetingElement = document.querySelector('.header p');
            if (greetingElement) {
                const visitCount = document.querySelector('.visit-count').textContent;
                greetingElement.innerHTML = `${getGreeting()}您是第<span class="visit-count">${visitCount}</span>位访问此页面的用户！`;
            }
        }

        // 初始化
        updateCurrentTime();
        updateCountdown();
        updateGreeting(); // 添加这行来初始化问候语
        
        // 每秒更新时间和倒计时
        setInterval(updateCurrentTime, 1000);
        setInterval(updateCountdown, 1000);
    </script>
</body>
</html>

