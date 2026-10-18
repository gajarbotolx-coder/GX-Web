<?php
// Master Admin Terminal - Backend Controller
session_start();
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");

$dataFile = __DIR__ . '/data.json';
$masterPass = "GX2.0";

// Helper function to read data
function loadServerData($filePath) {
    if (file_exists($filePath)) {
        $data = json_decode(file_get_contents($filePath), true);
        if (is_array($data)) return $data;
    }
    return [
        'tools' => [],
        'notifications' => [],
        'theme_index' => 0
    ];
}

// Helper function to save data safely
function saveServerData($filePath, $data) {
    return file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// AJAX / POST API Endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $providedPin = $_POST['pin'] ?? '';

    if ($providedPin !== $masterPass) {
        echo json_encode(['status' => 'error', 'message' => 'Security Alert: Access Denied! Invalid Master PIN.']);
        exit;
    }

    $currentData = loadServerData($dataFile);
    $action = $_POST['action'];

    if ($action === 'save_tool') {
        $idx = isset($_POST['index']) ? (int)$_POST['index'] : -1;
        $name = trim($_POST['name'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $img = trim($_POST['img'] ?? '');
        $desc = trim($_POST['desc'] ?? '');
        $icon = trim($_POST['icon'] ?? '') ?: 'fa-cube';
        $color = trim($_POST['color'] ?? '') ?: 'linear-gradient(135deg, #ff003b, #ff4b4b)';

        if (empty($name) || empty($url)) {
            echo json_encode(['status' => 'error', 'message' => 'Tool title and URL are required!']);
            exit;
        }

        $newTool = [
            'name' => $name,
            'url' => $url,
            'img' => $img,
            'desc' => $desc,
            'icon' => $icon,
            'color' => $color
        ];

        if ($idx >= 0 && isset($currentData['tools'][$idx])) {
            $currentData['tools'][$idx] = $newTool;
        } else {
            $currentData['tools'][] = $newTool;
        }

        saveServerData($dataFile, $currentData);
        echo json_encode(['status' => 'success', 'message' => 'Tool successfully saved to server & synced across all browsers!', 'data' => $currentData]);
        exit;
    }

    if ($action === 'delete_tool') {
        $idx = (int)($_POST['index'] ?? -1);
        if ($idx >= 0 && isset($currentData['tools'][$idx])) {
            array_splice($currentData['tools'], $idx, 1);
            saveServerData($dataFile, $currentData);
            echo json_encode(['status' => 'success', 'message' => 'Tool removed permanently!', 'data' => $currentData]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Tool not found!']);
        }
        exit;
    }

    if ($action === 'post_notif') {
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        if (empty($title) || empty($body)) {
            echo json_encode(['status' => 'error', 'message' => 'Title and body are required!']);
            exit;
        }

        array_unshift($currentData['notifications'], [
            'title' => $title,
            'body' => $body,
            'time' => 'Just now',
            'unread' => true
        ]);

        saveServerData($dataFile, $currentData);
        echo json_encode(['status' => 'success', 'message' => 'Notification published globally!', 'data' => $currentData]);
        exit;
    }

    if ($action === 'delete_notif') {
        $idx = (int)($_POST['index'] ?? -1);
        if ($idx >= 0 && isset($currentData['notifications'][$idx])) {
            array_splice($currentData['notifications'], $idx, 1);
            saveServerData($dataFile, $currentData);
            echo json_encode(['status' => 'success', 'message' => 'Notification removed!', 'data' => $currentData]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Notification not found!']);
        }
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action!']);
    exit;
}

$initialData = loadServerData($dataFile);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>GX DEV | Master Admin Terminal</title>
    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary: #ff003b;
            --primary-glow: rgba(255, 0, 59, 0.45);
            --primary-subtle: rgba(255, 0, 59, 0.12);
            --accent-grad: linear-gradient(135deg, #ff003b 0%, #ff4b4b 100%);
            --bg-body: #05070d;
            --bg-card: rgba(13, 18, 30, 0.88);
            --border-color: rgba(255, 255, 255, 0.08);
            --border-glow: rgba(255, 0, 59, 0.35);
            --text-main: #ffffff;
            --text-sub: #94a3b8;
            --text-dim: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
            transition: all 0.25s ease;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px 15px;
            background-image: 
                radial-gradient(circle at 10% 10%, var(--primary-subtle) 0%, transparent 45%),
                radial-gradient(circle at 90% 85%, rgba(255, 0, 59, 0.05) 0%, transparent 50%);
        }

        .admin-box {
            width: 100%;
            max-width: 720px;
            background: var(--bg-card);
            border: 1px solid var(--border-glow);
            border-radius: 24px;
            padding: 34px;
            box-shadow: 0 0 45px var(--primary-glow);
            backdrop-filter: blur(25px);
            position: relative;
            z-index: 1;
        }

        .admin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 18px;
            margin-bottom: 25px;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-brand img {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid var(--primary);
            box-shadow: 0 0 14px var(--primary-glow);
        }

        .admin-brand h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.35rem;
            font-weight: 800;
        }
        .admin-brand h2 span { color: var(--primary); text-shadow: 0 0 10px var(--primary-glow); }

        .back-link {
            color: var(--text-dim);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
        }
        .back-link:hover { 
            color: var(--primary); 
            border-color: var(--primary);
            box-shadow: 0 0 12px var(--primary-glow);
        }

        .form-group {
            margin-bottom: 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--text-sub);
        }

        .form-input {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            color: #fff;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.92rem;
            outline: none;
        }
        .form-input:focus {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.07);
            box-shadow: 0 0 14px var(--primary-glow);
        }

        .btn-submit {
            background: var(--accent-grad);
            color: #000;
            border: none;
            padding: 13px 20px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 20px var(--primary-glow);
        }

        .panel-section {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 22px;
        }

        .section-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.12rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .color-presets-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 6px;
            margin-bottom: 6px;
        }

        .color-preset-btn {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .color-preset-btn:hover { transform: scale(1.1); border-color: #fff; }

        .tools-list-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 280px;
            overflow-y: auto;
            margin-top: 14px;
        }

        .tool-row-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            padding: 12px 16px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.9rem;
        }
        .tool-row-item:hover {
            border-color: var(--border-glow);
            background: rgba(255, 255, 255, 0.05);
        }

        .tool-info-left {
            display: flex;
            align-items: center;
            gap: 12px;
            overflow: hidden;
        }

        .tool-preview-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: #fff;
            flex-shrink: 0;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .tool-preview-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .tool-row-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }

        .mini-btn {
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-edit { background: #3b82f6; color: #fff; }
        .btn-edit:hover { background: #2563eb; }
        .btn-delete { background: #ef4444; color: #fff; }
        .btn-delete:hover { background: #dc2626; }

        .notif-row-item {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            padding: 14px 16px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
        }
        .notif-row-item:hover { border-color: var(--border-glow); }

        .server-sync-badge {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #10b981;
            font-size: 0.75rem;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>

    <div class="admin-box">
        <!-- Header Bar -->
        <div class="admin-header">
            <div class="admin-brand">
                <img src="https://cdn.phototourl.com/free/2026-09-01-6efe2eeb-1128-429d-b7a5-672c20efb3a7.jpg" 
                     alt="GX DEV" 
                     onerror="this.src='https://placehold.co/80x80/ff003b/000?text=GX'">
                <div>
                    <h2>GX DEV <span>ADMIN</span></h2>
                    <span class="server-sync-badge"><i class="fa-solid fa-cloud"></i> Server JSON Database</span>
                </div>
            </div>
            <a href="index.php" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> View Hub
            </a>
        </div>

        <!-- Security PIN Screen -->
        <div id="pinScreen">
            <div class="form-group">
                <label>Terminal Master Password (Password: GX2.0)</label>
                <input type="password" id="masterPinInput" class="form-input" placeholder="Enter master password (e.g. GX2.0)" autofocus>
            </div>
            <button class="btn-submit" onclick="unlockAdmin()">
                <i class="fa-solid fa-unlock"></i> Unlock Terminal
            </button>
        </div>

        <!-- Admin Control Console -->
        <div id="adminConsole" style="display: none;">
            <!-- Broadcast Notification Section -->
            <div class="panel-section">
                <div class="section-title">
                    <i class="fa-solid fa-bullhorn"></i> Broadcast Announcement
                </div>
                <div class="form-group">
                    <input type="text" id="notifTitleInput" class="form-input" placeholder="Announcement Title (e.g. Server Update / New Tool Added)">
                </div>
                <div class="form-group">
                    <textarea id="notifBodyInput" class="form-input" rows="2" placeholder="Announcement message details..."></textarea>
                </div>
                <button class="btn-submit" onclick="postNotification()">
                    <i class="fa-solid fa-paper-plane"></i> Publish Global Notification
                </button>

                <div id="adminNotifsList" style="margin-top: 14px;"></div>
            </div>

            <!-- Manage Web Tools Section -->
            <div class="panel-section">
                <div class="section-title" id="toolFormTitle">
                    <i class="fa-solid fa-plus"></i> Add New Web Tool
                </div>
                <input type="hidden" id="editToolIndex" value="-1">

                <div class="form-group">
                    <label>Tool Title</label>
                    <input type="text" id="toolNameInput" class="form-input" placeholder="Tool Name (e.g. Temp Mail)">
                </div>

                <div class="form-group">
                    <label>Tool Link / URL (Supports https://, http://, or local /bgremover/)</label>
                    <input type="text" id="toolUrlInput" class="form-input" placeholder="e.g. https://gxdev.site.je or /bgremover/">
                </div>

                <div class="form-group">
                    <label>Tool Image URL / Logo Link (Direct Photo link)</label>
                    <input type="text" id="toolImgInput" class="form-input" placeholder="e.g. https://cdn.phototourl.com/free/...jpg or /images/tool.png">
                </div>

                <div class="form-group">
                    <label>Short Description</label>
                    <input type="text" id="toolDescInput" class="form-input" placeholder="Brief summary of what this tool does...">
                </div>

                <div class="form-group">
                    <label>FontAwesome Icon Class (Fallback icon)</label>
                    <input type="text" id="toolIconInput" class="form-input" placeholder="e.g. fa-envelope, fa-image, fa-file-zipper, fa-bolt">
                </div>

                <div class="form-group">
                    <label>Custom Gradient / Background</label>
                    <input type="text" id="toolColorInput" class="form-input" placeholder="e.g. linear-gradient(135deg, #ff003b, #ff4b4b)">
                    
                    <div class="color-presets-row">
                        <div class="color-preset-btn" style="background: linear-gradient(135deg, #ff003b, #ff4b4b);" onclick="setPresetGrad('linear-gradient(135deg, #ff003b, #ff4b4b)')"></div>
                        <div class="color-preset-btn" style="background: linear-gradient(135deg, #a855f7, #6366f1);" onclick="setPresetGrad('linear-gradient(135deg, #a855f7, #6366f1)')"></div>
                        <div class="color-preset-btn" style="background: linear-gradient(135deg, #0ea5e9, #3b82f6);" onclick="setPresetGrad('linear-gradient(135deg, #0ea5e9, #3b82f6)')"></div>
                        <div class="color-preset-btn" style="background: linear-gradient(135deg, #10b981, #059669);" onclick="setPresetGrad('linear-gradient(135deg, #10b981, #059669)')"></div>
                        <div class="color-preset-btn" style="background: linear-gradient(135deg, #ffaa00, #f59e0b);" onclick="setPresetGrad('linear-gradient(135deg, #ffaa00, #f59e0b)')"></div>
                        <div class="color-preset-btn" style="background: linear-gradient(135deg, #ec4899, #f43f5e);" onclick="setPresetGrad('linear-gradient(135deg, #ec4899, #f43f5e)')"></div>
                    </div>
                </div>

                <button class="btn-submit" id="saveToolBtn" onclick="saveTool()">
                    <i class="fa-solid fa-floppy-disk"></i> Save Web Tool to Server
                </button>

                <div class="section-title" style="margin-top: 24px; font-size: 0.95rem; color: var(--text-sub);">
                    Existing Tools on Server (Instant Global Sync):
                </div>
                <div class="tools-list-container" id="adminToolsList"></div>
            </div>
        </div>
    </div>

    <!-- Admin Controller Script -->
    <script>
        let serverData = <?= json_encode($initialData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        let authenticatedPin = "";

        function unlockAdmin() {
            const pin = document.getElementById('masterPinInput').value.trim();
            if (pin === "GX2.0") {
                authenticatedPin = pin;
                document.getElementById('pinScreen').style.display = 'none';
                document.getElementById('adminConsole').style.display = 'block';
                renderAdminTools();
                renderAdminNotifs();
            } else {
                alert("Security Alert: Invalid Password / Access Denied!");
            }
        }

        document.getElementById('masterPinInput').addEventListener('keyup', (e) => {
            if (e.key === 'Enter') unlockAdmin();
        });

        function setPresetGrad(grad) {
            document.getElementById('toolColorInput').value = grad;
        }

        function renderAdminTools() {
            const container = document.getElementById('adminToolsList');
            const tools = serverData.tools || [];

            if (tools.length === 0) {
                container.innerHTML = `<div style="font-size:0.85rem; color:var(--text-dim); text-align:center; padding:15px;">No tools configured on server yet.</div>`;
                return;
            }

            container.innerHTML = tools.map((t, i) => `
                <div class="tool-row-item">
                    <div class="tool-info-left">
                        <div class="tool-preview-icon" style="background: ${t.color || 'var(--accent-grad)'}">
                            ${t.img && t.img.trim() !== '' ? `<img src="${t.img.trim()}" alt="${t.name}">` : `<i class="fa-solid ${t.icon || 'fa-cube'}"></i>`}
                        </div>
                        <div>
                            <strong style="color:#fff;">${t.name}</strong>
                            <div style="font-size:0.78rem; color:var(--text-dim);">${t.url}</div>
                        </div>
                    </div>
                    <div class="tool-row-actions">
                        <button class="mini-btn btn-edit" onclick="editTool(${i})"><i class="fa-solid fa-pen"></i> Edit</button>
                        <button class="mini-btn btn-delete" onclick="deleteTool(${i})"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            `).join('');
        }

        function renderAdminNotifs() {
            const container = document.getElementById('adminNotifsList');
            const notifs = serverData.notifications || [];

            if (notifs.length === 0) {
                container.innerHTML = `<div style="font-size:0.85rem; color:var(--text-dim); text-align:center; padding:15px;">No active broadcast notifications.</div>`;
                return;
            }
            container.innerHTML = notifs.map((n, i) => `
                <div class="notif-row-item">
                    <div>
                        <strong style="color:#fff;">${n.title}</strong>
                        <div style="font-size:0.82rem; color:var(--text-sub);">${n.body}</div>
                    </div>
                    <button class="mini-btn btn-delete" onclick="deleteNotif(${i})"><i class="fa-solid fa-trash"></i></button>
                </div>
            `).join('');
        }

        async function saveTool() {
            const idx = parseInt(document.getElementById('editToolIndex').value);
            const name = document.getElementById('toolNameInput').value.trim();
            const url = document.getElementById('toolUrlInput').value.trim();
            const img = document.getElementById('toolImgInput').value.trim();
            const desc = document.getElementById('toolDescInput').value.trim();
            const icon = document.getElementById('toolIconInput').value.trim() || 'fa-cube';
            const color = document.getElementById('toolColorInput').value.trim() || 'linear-gradient(135deg, #ff003b, #ff4b4b)';

            if (!name || !url) {
                alert("Please fill in both Tool Title and Tool URL/Path!");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'save_tool');
            formData.append('pin', authenticatedPin);
            formData.append('index', idx);
            formData.append('name', name);
            formData.append('url', url);
            formData.append('img', img);
            formData.append('desc', desc);
            formData.append('icon', icon);
            formData.append('color', color);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    serverData = result.data;
                    resetToolForm();
                    renderAdminTools();
                    alert("Tool successfully saved on server! All browsers will now show this update.");
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert("Server Connection Error while saving!");
            }
        }

        function editTool(idx) {
            const t = serverData.tools[idx];
            document.getElementById('editToolIndex').value = idx;
            document.getElementById('toolNameInput').value = t.name;
            document.getElementById('toolUrlInput').value = t.url;
            document.getElementById('toolImgInput').value = t.img || '';
            document.getElementById('toolDescInput').value = t.desc;
            document.getElementById('toolIconInput').value = t.icon || '';
            document.getElementById('toolColorInput').value = t.color;

            document.getElementById('toolFormTitle').innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Edit Tool: ' + t.name;
            document.getElementById('saveToolBtn').innerHTML = '<i class="fa-solid fa-check"></i> Update Tool on Server';
            document.getElementById('toolNameInput').focus();
        }

        async function deleteTool(idx) {
            const t = serverData.tools[idx];
            if (confirm(`Are you sure you want to delete "${t.name}" from server?`)) {
                const formData = new FormData();
                formData.append('action', 'delete_tool');
                formData.append('pin', authenticatedPin);
                formData.append('index', idx);

                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.status === 'success') {
                        serverData = result.data;
                        renderAdminTools();
                    } else {
                        alert(result.message);
                    }
                } catch (err) {
                    alert("Error deleting tool from server!");
                }
            }
        }

        function resetToolForm() {
            document.getElementById('editToolIndex').value = "-1";
            document.getElementById('toolNameInput').value = "";
            document.getElementById('toolUrlInput').value = "";
            document.getElementById('toolImgInput').value = "";
            document.getElementById('toolDescInput').value = "";
            document.getElementById('toolIconInput').value = "";
            document.getElementById('toolColorInput').value = "";
            document.getElementById('toolFormTitle').innerHTML = '<i class="fa-solid fa-plus"></i> Add New Web Tool';
            document.getElementById('saveToolBtn').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Web Tool to Server';
        }

        async function postNotification() {
            const title = document.getElementById('notifTitleInput').value.trim();
            const body = document.getElementById('notifBodyInput').value.trim();
            if (!title || !body) {
                alert("Please enter Announcement Title and Message!");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'post_notif');
            formData.append('pin', authenticatedPin);
            formData.append('title', title);
            formData.append('body', body);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    serverData = result.data;
                    document.getElementById('notifTitleInput').value = '';
                    document.getElementById('notifBodyInput').value = '';
                    renderAdminNotifs();
                    alert("Broadcast notification published globally to index.php!");
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert("Error publishing notification!");
            }
        }

        async function deleteNotif(idx) {
            const formData = new FormData();
            formData.append('action', 'delete_notif');
            formData.append('pin', authenticatedPin);
            formData.append('index', idx);

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.status === 'success') {
                    serverData = result.data;
                    renderAdminNotifs();
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert("Error deleting notification!");
            }
        }
    </script>
</body>
</html>