/* DadsFam Cart Recovery — Admin JS
 *  - Dashboard line chart (no external libs)
 *  - Preview / Send Test / Run Cron modal interactions
 */
(function(){
    'use strict';

    function ready(fn){
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    /* =====================================================
       CHART
       ===================================================== */
    function drawChart(){
        var canvas = document.getElementById('dfca-chart');
        if (!canvas) return;
        var series = [];
        try { series = JSON.parse(canvas.getAttribute('data-series')) || []; } catch(e){ series = []; }
        var ctx = canvas.getContext('2d');
        var W = canvas.clientWidth, H = canvas.height = 240;
        canvas.width  = W * (window.devicePixelRatio || 1);
        canvas.height = H * (window.devicePixelRatio || 1);
        ctx.scale(window.devicePixelRatio || 1, window.devicePixelRatio || 1);

        if (!series.length) {
            ctx.fillStyle = '#9aa6b2';
            ctx.font = '13px -apple-system,Segoe UI,Roboto,sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('No data in this date range yet — abandoned carts will appear here.', W/2, H/2);
            return;
        }

        var pad = { l: 44, r: 12, t: 14, b: 24 };
        var w = W - pad.l - pad.r;
        var h = H - pad.t - pad.b;

        var max = 0;
        series.forEach(function(d){
            max = Math.max(max, parseFloat(d.recoverable||0), parseFloat(d.recovered||0));
        });
        max = max > 0 ? max * 1.15 : 100;

        // Grid + Y labels
        ctx.strokeStyle = '#eef1f5';
        ctx.fillStyle   = '#9aa6b2';
        ctx.font        = '11px -apple-system,Segoe UI,Roboto,sans-serif';
        ctx.lineWidth   = 1;
        for (var i = 0; i <= 4; i++) {
            var y = pad.t + (h / 4) * i;
            ctx.beginPath(); ctx.moveTo(pad.l, y); ctx.lineTo(W - pad.r, y); ctx.stroke();
            var v = max - (max / 4) * i;
            ctx.textAlign = 'right'; ctx.fillText(Math.round(v), pad.l - 6, y + 4);
        }

        function pointsFor(key, color){
            var pts = series.map(function(d, i){
                var x = pad.l + (series.length > 1 ? (w / (series.length - 1)) * i : w / 2);
                var y = pad.t + h - ((parseFloat(d[key] || 0) / max) * h);
                return { x: x, y: y };
            });
            ctx.strokeStyle = color;
            ctx.lineWidth   = 2.5;
            ctx.beginPath();
            pts.forEach(function(p, i){
                if (i === 0) ctx.moveTo(p.x, p.y);
                else {
                    var prev = pts[i-1];
                    var cx = (prev.x + p.x) / 2;
                    ctx.bezierCurveTo(cx, prev.y, cx, p.y, p.x, p.y);
                }
            });
            ctx.stroke();
            pts.forEach(function(p){
                ctx.fillStyle = color;
                ctx.beginPath(); ctx.arc(p.x, p.y, 3, 0, Math.PI * 2); ctx.fill();
            });
        }
        pointsFor('recoverable', '#1a4fa0');
        pointsFor('recovered',   '#f59e0b');

        // X labels
        ctx.fillStyle = '#9aa6b2';
        ctx.textAlign = 'center';
        if (series.length) {
            ctx.fillText(formatDay(series[0].day), pad.l, H - 6);
            if (series.length > 1) {
                ctx.fillText(formatDay(series[series.length-1].day), W - pad.r, H - 6);
            }
        }
    }

    function formatDay(d){
        if (!d) return '';
        var dt = new Date(d + 'T00:00:00');
        return dt.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    }

    /* =====================================================
       MODAL
       ===================================================== */
    function openModal(title, bodyHtml, footHtml){
        closeModal();
        var bk = document.createElement('div');
        bk.className = 'dfca-modal-backdrop';
        bk.innerHTML = '<div class="dfca-modal">'
            + '<div class="dfca-modal-head"><h3>' + escapeHtml(title) + '</h3>'
            + '<button type="button" class="dfca-modal-close" aria-label="Close">×</button></div>'
            + '<div class="dfca-modal-body">' + bodyHtml + '</div>'
            + (footHtml ? '<div class="dfca-modal-foot">' + footHtml + '</div>' : '')
            + '</div>';
        document.body.appendChild(bk);
        bk.addEventListener('click', function(e){ if (e.target === bk) closeModal(); });
        bk.querySelector('.dfca-modal-close').addEventListener('click', closeModal);
    }
    function closeModal(){
        var ex = document.querySelector('.dfca-modal-backdrop');
        if (ex) ex.remove();
    }
    function escapeHtml(s){
        return String(s||'').replace(/[&<>"']/g, function(c){
            return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;' }[c];
        });
    }

    /* =====================================================
       PREVIEW
       ===================================================== */
    function bindPreviewButtons(){
        document.querySelectorAll('.dfca-preview-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                var id = btn.getAttribute('data-id');
                openModal('Loading preview...', '<p style="text-align:center;padding:30px;color:#6b7a8d;">Loading...</p>');

                fetch(DFCA.rest + 'preview', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': DFCA.nonce },
                    credentials: 'same-origin',
                    body: JSON.stringify({ template_id: parseInt(id, 10) })
                })
                .then(function(r){ return r.json(); })
                .then(function(json){
                    if (json.error) {
                        openModal('Preview failed', '<p style="color:#e63946">' + escapeHtml(json.error) + '</p>');
                        return;
                    }
                    var iframeId = 'dfca-prev-' + Date.now();
                    openModal(
                        'Email Preview (sample data)',
                        '<p style="margin:0 0 10px;color:#6b7a8d;font-size:0.86em;">This is how the email will appear to your customer, with sample cart contents.</p>'
                        + '<iframe id="' + iframeId + '" class="dfca-iframe-preview"></iframe>',
                        '<button type="button" class="dfca-btn dfca-btn-light dfca-modal-close-btn">Close</button>'
                    );
                    var iframe = document.getElementById(iframeId);
                    iframe.srcdoc = json.html;
                    document.querySelector('.dfca-modal-close-btn').addEventListener('click', closeModal);
                })
                .catch(function(err){
                    openModal('Preview failed', '<p style="color:#e63946">' + escapeHtml(String(err)) + '</p>');
                });
            });
        });
    }

    /* =====================================================
       SEND TEST
       ===================================================== */
    function bindTestButtons(){
        document.querySelectorAll('.dfca-test-btn').forEach(function(btn){
            btn.addEventListener('click', function(){
                var id = btn.getAttribute('data-id');
                var defaultEmail = DFCA.admin_email || '';
                openModal(
                    'Send Test Email',
                    '<p style="margin:0 0 10px;color:#6b7a8d;font-size:0.88em;">Send a test version of this template to any email address — uses sample cart data.</p>'
                    + '<div class="dfca-field"><label>Recipient Email</label>'
                    + '<input type="email" id="dfca-test-email" value="' + escapeHtml(defaultEmail) + '" placeholder="you@example.com" style="width:100%;padding:10px 14px;border:1px solid #dde3ea;border-radius:8px;font-size:14px;"></div>'
                    + '<div id="dfca-test-result"></div>',
                    '<button type="button" class="dfca-btn dfca-btn-light dfca-modal-close-btn">Cancel</button>'
                    + '<button type="button" class="dfca-btn dfca-btn-primary" id="dfca-send-test-go">📤 Send Test</button>'
                );
                document.querySelector('.dfca-modal-close-btn').addEventListener('click', closeModal);
                document.getElementById('dfca-send-test-go').addEventListener('click', function(){
                    var to = document.getElementById('dfca-test-email').value;
                    var resultEl = document.getElementById('dfca-test-result');
                    resultEl.innerHTML = '<p style="color:#6b7a8d;margin:10px 0 0;">Sending...</p>';

                    var fd = new FormData();
                    fd.append('action', 'dfca_send_test_email');
                    fd.append('nonce', DFCA.ajax_nonce);
                    fd.append('template_id', id);
                    fd.append('email', to);

                    fetch(DFCA.ajax, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: fd
                    })
                    .then(function(r){ return r.json(); })
                    .then(function(json){
                        if (!json.success) {
                            var msg = (json.data && json.data.error) ? json.data.error : 'Send failed.';
                            resultEl.innerHTML = '<div class="dfca-flash dfca-flash-error" style="margin:14px 0 0;">❌ ' + escapeHtml(msg) + '</div>';
                        } else {
                            resultEl.innerHTML = '<div class="dfca-flash dfca-flash-success" style="margin:14px 0 0;">✅ Test email sent to ' + escapeHtml(json.data.sent_to) + '. Check your inbox (and spam folder).</div>';
                        }
                    })
                    .catch(function(err){
                        resultEl.innerHTML = '<div class="dfca-flash dfca-flash-error" style="margin:14px 0 0;">❌ ' + escapeHtml(String(err)) + '</div>';
                    });
                });
            });
        });
    }

    /* =====================================================
       RUN CRON
       ===================================================== */
    function bindRunCron(){
        var btn = document.getElementById('dfca-run-cron');
        if (!btn) return;
        btn.addEventListener('click', function(){
            btn.disabled = true;
            btn.textContent = '⏳ Processing...';

            fetch(DFCA.rest + 'run-cron', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': DFCA.nonce },
                credentials: 'same-origin'
            })
            .then(function(r){ return r.json(); })
            .then(function(json){
                btn.disabled = false;
                btn.textContent = '🔄 Run Abandonment Check Now';
                if (json && json.success) {
                    var diff = function(k){
                        var d = (json.after[k]||0) - (json.before[k]||0);
                        return d === 0 ? '0' : (d > 0 ? '+' + d : String(d));
                    };
                    openModal(
                        'Cron processed',
                        '<p style="margin:0 0 12px;">Run complete. Status changes:</p>'
                        + '<table class="dfca-status-table">'
                        + '<tr><th>Pending</th><td>' + json.before.pending + ' → ' + json.after.pending + ' (' + diff('pending') + ')</td></tr>'
                        + '<tr><th>Abandoned</th><td>' + json.before.abandoned + ' → ' + json.after.abandoned + ' (' + diff('abandoned') + ')</td></tr>'
                        + '<tr><th>Recovered</th><td>' + json.before.recovered + ' → ' + json.after.recovered + ' (' + diff('recovered') + ')</td></tr>'
                        + '<tr><th>Lost</th><td>' + json.before.lost + ' → ' + json.after.lost + ' (' + diff('lost') + ')</td></tr>'
                        + '</table>'
                        + '<p style="margin:12px 0 0;color:#6b7a8d;font-size:0.86em;">If an abandoned cart had an active template whose trigger time has elapsed, an email was sent. Reload the page to see updated counts.</p>',
                        '<button type="button" class="dfca-btn dfca-btn-primary dfca-modal-close-btn">OK</button>'
                    );
                    document.querySelector('.dfca-modal-close-btn').addEventListener('click', function(){
                        closeModal();
                        location.reload();
                    });
                } else {
                    openModal('Cron failed', '<p style="color:#e63946">Could not run cron. Check error logs.</p>');
                }
            })
            .catch(function(err){
                btn.disabled = false;
                btn.textContent = '🔄 Run Abandonment Check Now';
                openModal('Cron failed', '<p style="color:#e63946">' + escapeHtml(String(err)) + '</p>');
            });
        });
    }

    /* =====================================================
       BOOT
       ===================================================== */
    ready(function(){
        drawChart();
        bindPreviewButtons();
        bindTestButtons();
        bindRunCron();
        window.addEventListener('resize', function(){
            clearTimeout(window._dfcaRz);
            window._dfcaRz = setTimeout(drawChart, 150);
        });
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape') closeModal();
        });
    });
})();
