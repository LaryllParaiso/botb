/**
 * WebSocket Server — Battle of the Bands Tabulator
 *
 * Handles real-time communication between admin and judge clients.
 *
 * Events (from PHP → WS server via internal HTTP):
 *   POST /notify  { event: 'band_change' | 'scores_submitted' | 'admin_update', data: {...} }
 *
 * Events (WS server → browser clients):
 *   { event: 'band_change', data: { band_id, band } }
 *   { event: 'scores_submitted', data: { band_id, judge_id } }
 *   { event: 'admin_update', data: { type } }
 */

const http = require('http');
const { WebSocketServer } = require('ws');

const WS_PORT = 8081;
const HTTP_PORT = 8082; // Internal HTTP port for PHP notifications

// ============================================
// WebSocket Server (browser clients connect here)
// ============================================

const wss = new WebSocketServer({ port: WS_PORT });

// Track clients by role
const clients = new Map(); // ws → { role: 'judge'|'admin', userId }

wss.on('connection', (ws, req) => {
    console.log(`[WS] New connection from ${req.socket.remoteAddress}`);

    // Client sends a register message on connect: { action: 'register', role: 'judge'|'admin', userId }
    ws.on('message', (raw) => {
        try {
            const msg = JSON.parse(raw);

            if (msg.action === 'register') {
                clients.set(ws, { role: msg.role, userId: msg.userId || null });
                console.log(`[WS] Registered: role=${msg.role}, userId=${msg.userId}`);
                ws.send(JSON.stringify({ event: 'registered', role: msg.role }));
            }
        } catch (e) {
            console.error('[WS] Bad message:', e.message);
        }
    });

    ws.on('close', () => {
        clients.delete(ws);
        console.log('[WS] Client disconnected');
    });

    ws.on('error', (err) => {
        console.error('[WS] Error:', err.message);
        clients.delete(ws);
    });
});

/**
 * Broadcast a message to clients filtered by role
 * @param {string|null} targetRole - 'judge', 'admin', or null for all
 * @param {object} payload - { event, data }
 */
function broadcast(targetRole, payload) {
    const msg = JSON.stringify(payload);
    let count = 0;

    for (const [ws, info] of clients) {
        if (ws.readyState === ws.OPEN) {
            if (!targetRole || info.role === targetRole) {
                ws.send(msg);
                count++;
            }
        }
    }

    console.log(`[WS] Broadcast "${payload.event}" to ${count} ${targetRole || 'all'} client(s)`);
}

// ============================================
// Internal HTTP Server (PHP notifies here)
// ============================================

const httpServer = http.createServer((req, res) => {
    // CORS for local only
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    if (req.method === 'OPTIONS') {
        res.writeHead(204);
        res.end();
        return;
    }

    if (req.method === 'POST' && req.url === '/notify') {
        let body = '';
        req.on('data', chunk => { body += chunk; });
        req.on('end', () => {
            try {
                const payload = JSON.parse(body);
                const event = payload.event;
                const data = payload.data || {};

                console.log(`[HTTP] Received notify: ${event}`);

                switch (event) {
                    case 'band_change':
                        // Notify all judges
                        broadcast('judge', { event: 'band_change', data });
                        // Also notify admins so pending judges panel refreshes
                        broadcast('admin', { event: 'admin_update', data: { type: 'band_change' } });
                        break;

                    case 'scores_submitted':
                        // Notify admins to refresh pending judges / bands
                        broadcast('admin', { event: 'scores_submitted', data });
                        break;

                    case 'admin_update':
                        // Notify all admins (band/judge CRUD)
                        broadcast('admin', { event: 'admin_update', data });
                        break;

                    default:
                        // Broadcast to everyone
                        broadcast(null, { event, data });
                }

                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: true }));
            } catch (e) {
                console.error('[HTTP] Parse error:', e.message);
                res.writeHead(400, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: false, message: 'Invalid JSON' }));
            }
        });
    } else {
        // Health check
        if (req.method === 'GET' && req.url === '/health') {
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({
                status: 'ok',
                clients: clients.size,
                uptime: process.uptime()
            }));
            return;
        }

        res.writeHead(404);
        res.end('Not found');
    }
});

httpServer.listen(HTTP_PORT, '127.0.0.1', () => {
    console.log(`[HTTP] Internal notification server on http://127.0.0.1:${HTTP_PORT}`);
});

console.log(`[WS] WebSocket server running on ws://localhost:${WS_PORT}`);
console.log('[WS] Waiting for connections...');
