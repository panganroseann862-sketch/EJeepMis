<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - E-Jeep Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsqr/1.4.0/jsQR.min.js"></script>
    <style>
        body { background: #f8fafc; }
        .sidebar { background: linear-gradient(to bottom, #000066, #000080); }
        #qr-video { width: 100%; border-radius: 12px; }
        #qr-canvas { display: none; }
        .scan-box { position: relative; display: inline-block; width: 100%; }
        .scan-line {
            position: absolute;
            left: 10%; width: 80%; height: 2px;
            background: #22c55e;
            animation: scanAnim 2s linear infinite;
            box-shadow: 0 0 8px #22c55e;
        }
        @keyframes scanAnim { 0% { top: 10%; } 100% { top: 90%; } }
        .log-entry { animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body class="font-sans antialiased">

<div class="flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="sidebar w-64 flex flex-col shadow-2xl">
        <div class="p-6 border-b border-white/10 flex items-center gap-3">
            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <div>
                <p class="text-white font-bold text-sm">E-Jeep MIS</p>
                <p class="text-white/50 text-xs">Student Portal</p>
            </div>
        </div>

        <nav class="flex-1 p-4 space-y-1">
            <a href="#" onclick="showSection('scanner')"
               class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg text-white/80 hover:bg-white/10 hover:text-white transition"
               id="nav-scanner">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                QR Scanner
            </a>
            <a href="#" onclick="showSection('logs')"
               class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg text-white/80 hover:bg-white/10 hover:text-white transition"
               id="nav-logs">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Driver Logs
            </a>
        </nav>

        <div class="p-4 border-t border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-green-400 rounded-full flex items-center justify-center text-white font-bold text-sm">
                    {{ strtoupper(substr(auth()->user()->name ?? 'S', 0, 1)) }}
                </div>
                <div>
                    <p class="text-white text-sm font-semibold">{{ auth()->user()->name ?? 'Student' }}</p>
                    <p class="text-white/50 text-xs">{{ auth()->user()->email ?? '' }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="mt-3 flex items-center gap-2 text-white/50 hover:text-red-400 text-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-8">

        {{-- Page Header --}}
        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Student Dashboard</h2>
            <p class="text-sm text-gray-500 mt-1">E-Jeep Monitoring & Information System</p>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-3 gap-5 mb-6">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex items-center gap-4">
                <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Scans Today</p>
                    <p class="text-2xl font-semibold text-gray-800 mt-0.5" id="scan-count">0</p>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex items-center gap-4">
                <div class="w-11 h-11 bg-green-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Active Jeeps</p>
                    <p class="text-2xl font-semibold text-gray-800 mt-0.5" id="active-jeeps">0</p>
                </div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex items-center gap-4">
                <div class="w-11 h-11 bg-yellow-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Driver Logs</p>
                    <p class="text-2xl font-semibold text-gray-800 mt-0.5" id="log-count">0</p>
                </div>
            </div>
        </div>

        {{-- QR Scanner Section --}}
        <div id="section-scanner">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 mb-5">
                <h3 class="text-base font-semibold text-gray-800 mb-1">QR Code Scanner</h3>
                <p class="text-sm text-gray-500 mb-5">Scan the QR code to check in to the e-jeep.</p>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <div class="scan-box bg-black rounded-xl overflow-hidden" style="min-height:240px;">
                            <video id="qr-video" autoplay playsinline></video>
                            <canvas id="qr-canvas"></canvas>
                            <div class="scan-line" id="scan-line"></div>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button onclick="startScanner()"
                                class="flex-1 inline-flex items-center justify-center gap-2 bg-blue-800 hover:bg-blue-900 text-white text-sm py-2 rounded-lg font-medium transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Start Camera
                            </button>
                            <button onclick="stopScanner()"
                                class="flex-1 inline-flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm py-2 rounded-lg font-medium transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z"/>
                                </svg>
                                Stop
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Scan Result</p>
                            <div id="scan-result"
                                class="bg-gray-50 border border-dashed border-gray-200 rounded-xl p-4 min-h-[120px] flex items-center justify-center">
                                <p class="text-gray-400 text-sm text-center">No QR code scanned yet.<br>Click Start Camera to begin.</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-xs text-gray-500 mb-2">Or enter code manually:</p>
                            <div class="flex gap-2">
                                <input id="manual-code" type="text" placeholder="Enter code..."
                                    class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                                <button onclick="manualScan()"
                                    class="inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-lg font-medium transition">
                                    Submit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Scan History --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800">Scan History (Today)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jeep</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody id="scan-history" class="bg-white divide-y divide-gray-100">
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">No scan history yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Driver Logs Section --}}
        <div id="section-logs" class="hidden">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Driver Activity Logs</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Real-time updates from drivers</p>
                    </div>
                    <button onclick="refreshLogs()" id="refresh-btn"
                        class="inline-flex items-center gap-2 text-sm font-medium text-blue-700 border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                </div>
                <div id="log-list" class="divide-y divide-gray-100">
                    <p class="text-center text-gray-400 text-sm py-10">Loading driver logs...</p>
                </div>
            </div>
        </div>

    </main>
</div>

<script>
// ---- Navigation ----
function showSection(name) {
    document.getElementById('section-scanner').classList.add('hidden');
    document.getElementById('section-logs').classList.add('hidden');
    document.getElementById('section-' + name).classList.remove('hidden');
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('bg-white/20','text-white'));
    document.getElementById('nav-' + name).classList.add('bg-white/20','text-white');
}
showSection('scanner');

// ---- QR Scanner ----
let videoStream = null, scanning = false, scanCount = 0;

async function startScanner() {
    try {
        videoStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        const video = document.getElementById('qr-video');
        video.srcObject = videoStream;
        scanning = true;
        requestAnimationFrame(tick);
    } catch (e) {
        alert('Cannot access camera. Please allow camera permissions.');
    }
}

function stopScanner() {
    scanning = false;
    if (videoStream) videoStream.getTracks().forEach(t => t.stop());
    videoStream = null;
}

function tick() {
    if (!scanning) return;
    const video = document.getElementById('qr-video');
    const canvas = document.getElementById('qr-canvas');
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(img.data, img.width, img.height);
        if (code) { processScan(code.data); return; }
    }
    requestAnimationFrame(tick);
}

function processScan(data) {
    scanning = false;
    stopScanner();
    scanCount++;
    document.getElementById('scan-count').textContent = scanCount;

    document.getElementById('scan-result').innerHTML = `
        <div class="text-center">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p class="font-semibold text-green-600 text-sm">Scan Successful!</p>
            <p class="text-gray-500 text-xs mt-1 break-all">${data}</p>
        </div>`;

    addToHistory(data, 'E-Jeep #' + (Math.floor(Math.random()*3)+1), 'Boarded');
}

function manualScan() {
    const code = document.getElementById('manual-code').value.trim();
    if (!code) return alert('Please enter a code.');
    processScan(code);
    document.getElementById('manual-code').value = '';
}

function addToHistory(code, jeep, status) {
    const tbody = document.getElementById('scan-history');
    const now = new Date().toLocaleTimeString();
    const row = document.createElement('tr');
    row.className = 'log-entry hover:bg-gray-50 transition';
    row.innerHTML = `
        <td class="px-6 py-3 text-sm text-gray-500">${now}</td>
        <td class="px-6 py-3 font-mono text-gray-700 text-xs">${code.substring(0,20)}${code.length>20?'...':''}</td>
        <td class="px-6 py-3 text-sm text-gray-700">${jeep}</td>
        <td class="px-6 py-3">
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>${status}
            </span>
        </td>`;
    if (tbody.querySelector('td[colspan]')) tbody.innerHTML = '';
    tbody.prepend(row);
}

// ---- Driver Logs ----
const statusConfig = {
    'En Route': ['bg-blue-100 text-blue-700',  'bg-blue-500'],
    'Loading':  ['bg-yellow-100 text-yellow-700','bg-yellow-500'],
    'Standby':  ['bg-gray-100 text-gray-600',   'bg-gray-400'],
    'Completed':['bg-green-100 text-green-700', 'bg-green-500'],
};

function renderLogs(logs) {
    const container = document.getElementById('log-list');

    if (!logs.length) {
        container.innerHTML = '<p class="text-center text-gray-400 text-sm py-10">No active driver trips at the moment.</p>';
        document.getElementById('log-count').textContent = 0;
        document.getElementById('active-jeeps').textContent = 0;
        return;
    }

    const activeCount = logs.filter(l => l.status === 'En Route').length;
    document.getElementById('active-jeeps').textContent = activeCount;
    document.getElementById('log-count').textContent = logs.length;

    container.innerHTML = logs.map(l => {
        const [badgeClass, dotClass] = statusConfig[l.status] || ['bg-gray-100 text-gray-600', 'bg-gray-400'];
        return `
        <div class="log-entry flex items-center gap-4 px-6 py-4 hover:bg-gray-50 transition">
            <div class="w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800">
                    ${l.driver}
                    <span class="text-gray-400 font-normal">&middot; ${l.jeep}</span>
                </p>
                <p class="text-sm text-gray-500 mt-0.5">${l.action} at <span class="font-medium text-gray-700">${l.location}</span></p>
                <p class="text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ${l.time}
                </p>
            </div>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium flex-shrink-0 ${badgeClass}">
                <span class="w-1.5 h-1.5 rounded-full ${dotClass} inline-block"></span>
                ${l.status}
            </span>
        </div>`;
    }).join('');
}

function refreshLogs() {
    const btn = document.getElementById('refresh-btn');
    btn.innerHTML = `<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
    </svg> Refreshing...`;

    fetch('/student/driver-logs')
        .then(r => r.json())
        .then(data => {
            renderLogs(data);
            btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg> Refresh`;
        })
        .catch(() => {
            document.getElementById('log-list').innerHTML =
                '<p class="text-center text-red-400 text-sm py-10">Failed to load driver logs. Please try again.</p>';
            btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg> Refresh`;
        });
}

refreshLogs();
setInterval(refreshLogs, 30000);
</script>
</body>
</html>