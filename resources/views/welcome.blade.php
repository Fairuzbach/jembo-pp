<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jembocable - Purchase Request</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @livewireStyles
</head>

<body class="bg-gray-100 min-h-screen p-8">

    <livewire:purchase-request-form />

    @livewireScripts

    <script>
        const _s = document.createElement('style');
        _s.textContent = `
        @keyframes neo-in    { from{opacity:0;transform:scale(.92) translateY(16px)} to{opacity:1;transform:scale(1) translateY(0)} }
        @keyframes neo-out   { from{opacity:1;transform:scale(1)} to{opacity:0;transform:scale(.95) translateY(8px)} }
        @keyframes toast-in  { from{opacity:0;transform:translateX(110%)} to{opacity:1;transform:translateX(0)} }
        @keyframes toast-out { from{opacity:1;transform:translateX(0)} to{opacity:0;transform:translateX(110%)} }
        @keyframes check-draw  { from{stroke-dashoffset:80} to{stroke-dashoffset:0} }
        @keyframes icon-float  { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-4px)} }
        @keyframes shake-x     { 0%,100%{transform:translateX(0)} 20%,60%{transform:translateX(-4px)} 40%,80%{transform:translateX(4px)} }
        @keyframes ripple-out  { from{transform:scale(0);opacity:.4} to{transform:scale(2.8);opacity:0} }

        .neo-in    { animation: neo-in   .42s cubic-bezier(.34,1.32,.64,1) both }
        .neo-out   { animation: neo-out  .22s ease-in both }
        .tin       { animation: toast-in  .38s cubic-bezier(.34,1.32,.64,1) both }
        .tout      { animation: toast-out .25s ease-in both }

        .swal2-popup           { padding:0 !important; border-radius:28px !important }
        .swal2-html-container  { overflow:visible !important; margin:0 !important; padding:0 !important }
        .swal2-timer-progress-bar { height:3px !important; border-radius:0 !important }
        .swal2-backdrop-show   {
            backdrop-filter: blur(8px) !important;
            background: rgba(209,213,219,.55) !important;
        }
        .sw-wrap .swal2-popup  { width:400px !important }
        .st-wrap .swal2-popup  { width:340px !important }

        /* ── Neo base surface ── */
        .neo-card {
            background: #e8ecf0;
            border-radius: 28px;
            overflow: hidden;
        }

        /* ── Inset box (recessed) ── */
        .neo-inset {
            background: #e8ecf0;
            border-radius: 14px;
            box-shadow:
                inset 3px 3px 7px #c8ccd0,
                inset -3px -3px 7px #ffffff;
        }

        /* ── Raised pill button ── */
        .neo-btn {
            border: none; cursor: pointer; border-radius: 14px;
            font-size: 13px; font-weight: 700; letter-spacing: .02em;
            transition: all .18s cubic-bezier(.4,0,.2,1);
            position: relative; overflow: hidden;
        }
        .neo-btn:active { transform: scale(.97) !important }

        .neo-btn-primary {
            background: #e8ecf0;
            box-shadow: 5px 5px 12px #c2c6ca, -5px -5px 12px #ffffff;
        }
        .neo-btn-primary:hover {
            box-shadow: 3px 3px 8px #c2c6ca, -3px -3px 8px #ffffff;
        }

        .neo-btn-ghost {
            background: #e8ecf0;
            box-shadow: 3px 3px 8px #c2c6ca, -3px -3px 8px #ffffff;
        }
        .neo-btn-ghost:hover {
            box-shadow: 2px 2px 5px #c2c6ca, -2px -2px 5px #ffffff;
        }

        /* ── Icon orb ── */
        .neo-orb {
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }
        .neo-orb-raised {
            box-shadow: 8px 8px 18px #c2c6ca, -8px -8px 18px #ffffff;
        }

        /* ── Divider ── */
        .neo-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #c8ccd0 30%, #c8ccd0 70%, transparent);
            margin: 0 4px;
        }

        /* ── Toast card ── */
        .neo-toast {
            background: #e8ecf0;
            border-radius: 18px;
            box-shadow: 8px 8px 20px #c2c6ca, -8px -8px 20px #ffffff;
            overflow: hidden;
        }
    `;
        document.head.appendChild(_s);

        // ── Mixins ──────────────────────────────────────────────
        const SwalBase = Swal.mixin({
            background: 'transparent',
            showClass: {
                popup: 'neo-in'
            },
            hideClass: {
                popup: 'neo-out'
            },
            customClass: {
                container: 'sw-wrap'
            },
            buttonsStyling: false,
        });

        const SwalToast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
            background: 'transparent',
            showClass: {
                popup: 'tin'
            },
            hideClass: {
                popup: 'tout'
            },
            customClass: {
                container: 'st-wrap'
            },
            didOpen: el => {
                el.onmouseenter = Swal.stopTimer;
                el.onmouseleave = Swal.resumeTimer;
            },
        });

        // ── Builders ─────────────────────────────────────────────
        function buildSuccess({
            title,
            text,
            meta = []
        }) {
            const rows = meta.map(m => `
            <div style="display:flex;align-items:center;gap:10px;padding:9px 0;
                        border-bottom:1px solid rgba(180,185,190,.35);">
                <span style="font-size:15px">${m.icon}</span>
                <span style="color:#8a9099;font-size:12px;flex:1;font-weight:500">${m.label}</span>
                <span style="color:#2d3748;font-size:12px;font-weight:700;font-family:monospace">${m.value}</span>
            </div>`).join('');

            return `
        <div class="neo-card" style="box-shadow:12px 12px 28px #c2c6ca,-12px -12px 28px #ffffff;">
            <!-- Accent bar -->
            <div style="height:4px;background:linear-gradient(90deg,#34d399,#10b981,#059669)"></div>

            <div style="padding:30px 26px 26px">
                <!-- Icon orb -->
                <div style="margin:0 auto 20px;width:76px;height:76px;position:relative;
                            animation:icon-float 3.5s ease-in-out infinite">
                    <!-- Ripple -->
                    <div style="position:absolute;inset:0;border-radius:50%;
                                animation:ripple-out 2.4s ease-out infinite;
                                background:rgba(52,211,153,.15)"></div>
                    <div class="neo-orb neo-orb-raised" style="width:76px;height:76px;background:#e8ecf0">
                        <!-- Inner inset ring -->
                        <div style="width:56px;height:56px;border-radius:50%;
                                    background:#e8ecf0;
                                    box-shadow:inset 3px 3px 8px #c2c6ca,inset -3px -3px 8px #ffffff;
                                    display:flex;align-items:center;justify-content:center">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none"
                                 stroke="#10b981" stroke-width="2.8" stroke-linecap="round">
                                <polyline points="20 6 9 17 4 12"
                                          stroke-dasharray="80" stroke-dashoffset="80"
                                          style="animation:check-draw .5s .25s cubic-bezier(.34,1.56,.64,1) forwards"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Badge -->
                <div style="text-align:center;margin-bottom:10px">
                    <span style="display:inline-block;font-size:10px;font-weight:800;letter-spacing:.14em;
                                 text-transform:uppercase;color:#059669;padding:3px 12px;border-radius:99px;
                                 background:#e8ecf0;
                                 box-shadow:3px 3px 7px #c2c6ca,-3px -3px 7px #ffffff;
                                 margin-bottom:10px">
                        ✓ &nbsp;BERHASIL
                    </span>
                    <h2 style="margin:0;color:#1a202c;font-size:19px;font-weight:800;line-height:1.3">
                        ${title}
                    </h2>
                </div>

                <!-- Text -->
                <p style="text-align:center;color:#718096;font-size:13px;line-height:1.65;margin:8px 0 ${rows ? 18 : 20}px">
                    ${text}
                </p>

                <!-- Meta rows inset -->
                ${rows ? `
                        <div class="neo-inset" style="padding:4px 14px;margin-bottom:20px">
                            ${rows}
                        </div>` : ''}

                <!-- CTA -->
                <button onclick="Swal.close()" class="neo-btn neo-btn-primary"
                        style="width:100%;padding:13px;color:#059669;"
                        onmouseover="this.style.color='#047857'"
                        onmouseout="this.style.color='#059669'">
                    Oke, Mengerti
                </button>
            </div>
        </div>`;
        }

        function buildWarning({
            title,
            text,
            hint
        }) {
            return `
        <div class="neo-card" style="box-shadow:12px 12px 28px #c2c6ca,-12px -12px 28px #ffffff;
                                     animation:shake-x .45s .05s ease both">
            <!-- Accent bar -->
            <div style="height:4px;background:linear-gradient(90deg,#fbbf24,#f59e0b,#d97706)"></div>

            <div style="padding:30px 26px 26px">
                <!-- Icon orb -->
                <div style="margin:0 auto 20px;width:76px;height:76px">
                    <div class="neo-orb neo-orb-raised" style="width:76px;height:76px;background:#e8ecf0">
                        <div style="width:56px;height:56px;border-radius:50%;
                                    background:#e8ecf0;
                                    box-shadow:inset 3px 3px 8px #c2c6ca,inset -3px -3px 8px #ffffff;
                                    display:flex;align-items:center;justify-content:center">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none"
                                 stroke="#d97706" stroke-width="2.8" stroke-linecap="round">
                                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                <line x1="12" y1="9"  x2="12"    y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Badge -->
                <div style="text-align:center;margin-bottom:10px">
                    <span style="display:inline-block;font-size:10px;font-weight:800;letter-spacing:.14em;
                                 text-transform:uppercase;color:#d97706;padding:3px 12px;border-radius:99px;
                                 background:#e8ecf0;
                                 box-shadow:3px 3px 7px #c2c6ca,-3px -3px 7px #ffffff;
                                 margin-bottom:10px">
                        ⚠ &nbsp;PERHATIAN
                    </span>
                    <h2 style="margin:0;color:#1a202c;font-size:19px;font-weight:800;line-height:1.3">
                        ${title}
                    </h2>
                </div>

                <!-- Text -->
                <p style="text-align:center;color:#718096;font-size:13px;line-height:1.65;margin:8px 0 ${hint ? 16 : 20}px">
                    ${text}
                </p>

                <!-- Hint inset -->
                ${hint ? `
                        <div class="neo-inset" style="display:flex;gap:10px;padding:12px 14px;margin-bottom:20px">
                            <span style="font-size:16px;flex-shrink:0">💡</span>
                            <p style="margin:0;color:#92400e;font-size:12px;line-height:1.65;font-weight:500">${hint}</p>
                        </div>` : ''}

                <!-- Buttons -->
                <div style="display:grid;grid-template-columns:1fr 2fr;gap:10px">
                    <button onclick="Swal.close()" class="neo-btn neo-btn-ghost"
                            style="padding:12px;color:#718096;"
                            onmouseover="this.style.color='#4a5568'"
                            onmouseout="this.style.color='#718096'">
                        Tutup
                    </button>
                    <button onclick="Swal.close()" class="neo-btn neo-btn-primary"
                            style="padding:12px;color:#d97706;"
                            onmouseover="this.style.color='#b45309'"
                            onmouseout="this.style.color='#d97706'">
                        Pilih Barang Sekarang
                    </button>
                </div>
            </div>
        </div>`;
        }

        function buildToast({
            type,
            title,
            subtitle
        }) {
            const map = {
                success: {
                    bar: 'linear-gradient(90deg,#34d399,#10b981)',
                    color: '#059669',
                    icon: `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.8" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>`
                },
                warning: {
                    bar: 'linear-gradient(90deg,#fbbf24,#f59e0b)',
                    color: '#d97706',
                    icon: `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.8" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>`
                },
                info: {
                    bar: 'linear-gradient(90deg,#60a5fa,#3b82f6)',
                    color: '#2563eb',
                    icon: `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.8" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`
                },
                error: {
                    bar: 'linear-gradient(90deg,#f87171,#ef4444)',
                    color: '#dc2626',
                    icon: `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.8" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`
                },
            };
            const t = map[type] ?? map.info;

            return `
        <div class="neo-toast">
            <div style="height:3px;background:${t.bar}"></div>
            <div style="display:flex;align-items:center;gap:11px;padding:13px 14px">
                <!-- Icon orb -->
                <div style="width:36px;height:36px;flex-shrink:0;border-radius:10px;background:#e8ecf0;
                            box-shadow:4px 4px 9px #c2c6ca,-4px -4px 9px #ffffff;
                            display:flex;align-items:center;justify-content:center">
                    ${t.icon}
                </div>
                <!-- Text -->
                <div style="flex:1;min-width:0">
                    <div style="color:#1a202c;font-size:12.5px;font-weight:700;
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        ${title}
                    </div>
                    ${subtitle ? `
                            <div style="color:#a0aec0;font-size:11px;margin-top:2px;font-family:monospace;
                                        white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                ${subtitle}
                            </div>` : ''}
                </div>
                <!-- Close -->
                <button onclick="Swal.close()" style="
                    width:26px;height:26px;flex-shrink:0;border:none;cursor:pointer;
                    background:#e8ecf0;border-radius:7px;color:#a0aec0;font-size:12px;
                    display:flex;align-items:center;justify-content:center;padding:0;
                    box-shadow:3px 3px 6px #c2c6ca,-3px -3px 6px #ffffff;transition:all .15s"
                    onmouseover="this.style.color='#4a5568';this.style.boxShadow='inset 2px 2px 5px #c2c6ca,inset -2px -2px 5px #ffffff'"
                    onmouseout="this.style.color='#a0aec0';this.style.boxShadow='3px 3px 6px #c2c6ca,-3px -3px 6px #ffffff'">
                    ✕
                </button>
            </div>
        </div>`;
        }

        // ── Event listeners ──────────────────────────────────────
        window.addEventListener('swal', event => {
            const d = event.detail[0];
            if (d.icon === 'success') {
                SwalBase.fire({
                    html: buildSuccess({
                        title: d.title ?? 'Berhasil!',
                        text: d.text ?? '',
                        meta: d.meta ?? []
                    }),
                    showConfirmButton: false
                });
            } else if (d.icon === 'warning') {
                SwalBase.fire({
                    html: buildWarning({
                        title: d.title ?? 'Peringatan!',
                        text: d.text ?? '',
                        hint: d.hint ?? null
                    }),
                    showConfirmButton: false
                });
            } else {
                Swal.fire(d);
            }
        });

        window.addEventListener('swal-toast', event => {
            const d = event.detail[0];
            SwalToast.fire({
                html: buildToast({
                    type: d.icon ?? 'info',
                    title: d.title ?? '',
                    subtitle: d.text ?? null
                }),
                showConfirmButton: false
            });
        });
    </script>
</body>

</html>
