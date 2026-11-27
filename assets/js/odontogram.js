/**
 * DENTARA INTERACTIVE ODONTOGRAM ENGINE (SVG & FDI NUMBERING)
 * File: assets/js/odontogram.js
 */

window.Odontogram = (function() {
  'use strict';

  // Gigi Dewasa (Permanen): 18-11, 21-28, 48-41, 31-38
  const PERMANENT_TEETH = {
    upperRight: ['18','17','16','15','14','13','12','11'],
    upperLeft:  ['21','22','23','24','25','26','27','28'],
    lowerRight: ['48','47','46','45','44','43','42','41'],
    lowerLeft:  ['31','32','33','34','35','36','37','38']
  };

  // Gigi Susu (Desidui): 55-51, 61-65, 85-81, 71-75
  const PRIMARY_TEETH = {
    upperRight: ['55','54','53','52','51'],
    upperLeft:  ['61','62','63','64','65'],
    lowerRight: ['85','84','83','82','81'],
    lowerLeft:  ['71','72','73','74','75']
  };

  let currentCondition = 'karies';
  let toothState = {}; // Format: { '16': { 'oklusal': 'karies', 'mesial': 'tambalan' } }
  let isReadOnly = false;

  function init(containerId, initialState = {}, readOnly = false) {
    const container = document.getElementById(containerId);
    if (!container) return;

    toothState = Object.assign({}, initialState);
    isReadOnly = readOnly;

    render(container);
  }

  function setCondition(cond) {
    currentCondition = cond;
    document.querySelectorAll('.tool-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.cond === cond);
    });
  }

  function render(container) {
    container.innerHTML = `
      <div class="odontogram-wrapper">
        ${!isReadOnly ? renderToolbar() : ''}
        <div class="odontogram-canvas">
          <!-- RA (Rahang Atas) Permanen -->
          <div class="dental-arch arch-upper">
            <div class="arch-quadrant quadrant-18-11">${renderQuadrant(PERMANENT_TEETH.upperRight)}</div>
            <div class="arch-quadrant quadrant-21-28">${renderQuadrant(PERMANENT_TEETH.upperLeft)}</div>
          </div>

          <!-- RA Gigi Susu -->
          <div class="dental-arch arch-primary-upper" style="margin-top:10px;">
            <div class="arch-quadrant quadrant-55-51">${renderQuadrant(PRIMARY_TEETH.upperRight, true)}</div>
            <div class="arch-quadrant quadrant-61-65">${renderQuadrant(PRIMARY_TEETH.upperLeft, true)}</div>
          </div>

          <!-- RB Gigi Susu -->
          <div class="dental-arch arch-primary-lower" style="margin-top:20px;">
            <div class="arch-quadrant quadrant-85-81">${renderQuadrant(PRIMARY_TEETH.lowerRight, true)}</div>
            <div class="arch-quadrant quadrant-71-75">${renderQuadrant(PRIMARY_TEETH.lowerLeft, true)}</div>
          </div>

          <!-- RB (Rahang Bawah) Permanen -->
          <div class="dental-arch arch-lower" style="margin-top:10px;">
            <div class="arch-quadrant quadrant-48-41">${renderQuadrant(PERMANENT_TEETH.lowerRight)}</div>
            <div class="arch-quadrant quadrant-31-38">${renderQuadrant(PERMANENT_TEETH.lowerLeft)}</div>
          </div>
        </div>
        ${renderLegend()}
      </div>
    `;

    bindEvents(container);
  }

  function renderToolbar() {
    return `
      <div class="odontogram-toolbar">
        <div class="d-flex align-items-center gap-2">
          <span style="font-size:12px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Pilih Kondisi:</span>
          <div class="condition-tools">
            <button type="button" class="tool-btn ${currentCondition === 'karies' ? 'active' : ''}" data-cond="karies" onclick="Odontogram.setCondition('karies')">
              <span class="color-indicator" style="background:#EF4444;"></span> Karies (Gigi Berlubang)
            </button>
            <button type="button" class="tool-btn ${currentCondition === 'tambalan' ? 'active' : ''}" data-cond="tambalan" onclick="Odontogram.setCondition('tambalan')">
              <span class="color-indicator" style="background:#10B981;"></span> Tambalan Komposit
            </button>
            <button type="button" class="tool-btn ${currentCondition === 'saluran_akar' ? 'active' : ''}" data-cond="saluran_akar" onclick="Odontogram.setCondition('saluran_akar')">
              <span class="color-indicator" style="background:#3B82F6;"></span> Saluran Akar (PSA)
            </button>
            <button type="button" class="tool-btn ${currentCondition === 'tumpatan_sementara' ? 'active' : ''}" data-cond="tumpatan_sementara" onclick="Odontogram.setCondition('tumpatan_sementara')">
              <span class="color-indicator" style="background:#F59E0B;"></span> Tumpatan Sementara
            </button>
            <button type="button" class="tool-btn ${currentCondition === 'mahkota' ? 'active' : ''}" data-cond="mahkota" onclick="Odontogram.setCondition('mahkota')">
              <span class="color-indicator" style="background:#8B7CF6;"></span> Mahkota Tiruan (Crown)
            </button>
            <button type="button" class="tool-btn ${currentCondition === 'hilang' ? 'active' : ''}" data-cond="hilang" onclick="Odontogram.setCondition('hilang')">
              <span class="color-indicator" style="background:#64748B;"></span> Gigi Hilang / Dicabut
            </button>
            <button type="button" class="tool-btn ${currentCondition === 'sehat' ? 'active' : ''}" data-cond="sehat" onclick="Odontogram.setCondition('sehat')">
              <span class="color-indicator" style="background:#FFFFFF;"></span> Hapus / Sehat
            </button>
          </div>
        </div>
      </div>
    `;
  }

  function renderQuadrant(teethArray, isPrimary = false) {
    return teethArray.map(toothNum => renderToothSVG(toothNum, isPrimary)).join('');
  }

  function renderToothSVG(toothNum, isPrimary = false) {
    const toothData = toothState[toothNum] || {};
    const oklusal = toothData['oklusal'] || 'sehat';
    const mesial  = toothData['mesial']  || 'sehat';
    const distal  = toothData['distal']  || 'sehat';
    const bukal   = toothData['bukal']   || 'sehat';
    const lingual = toothData['lingual'] || 'sehat';

    const isHilang = toothData['seluruh'] === 'hilang';

    return `
      <div class="tooth-card ${isPrimary ? 'primary-tooth' : ''}" data-tooth="${toothNum}">
        <span class="tooth-number">${toothNum}</span>
        <svg class="tooth-svg" viewBox="0 0 40 40" data-tooth="${toothNum}">
          <!-- Bukal / Labial (Top Trapezoid) -->
          <polygon class="surface-part cond-${bukal}" data-tooth="${toothNum}" data-surface="bukal" points="0,0 40,0 30,10 10,10" />
          <!-- Mesial (Left Trapezoid) -->
          <polygon class="surface-part cond-${mesial}" data-tooth="${toothNum}" data-surface="mesial" points="0,0 10,10 10,30 0,40" />
          <!-- Distal (Right Trapezoid) -->
          <polygon class="surface-part cond-${distal}" data-tooth="${toothNum}" data-surface="distal" points="40,0 40,40 30,30 30,10" />
          <!-- Lingual / Palatal (Bottom Trapezoid) -->
          <polygon class="surface-part cond-${lingual}" data-tooth="${toothNum}" data-surface="lingual" points="0,40 10,30 30,30 40,40" />
          <!-- Oklusal / Central (Center Square) -->
          <rect class="surface-part cond-${oklusal}" data-tooth="${toothNum}" data-surface="oklusal" x="10" y="10" width="20" height="20" />
          <!-- Cross 'X' Overlay jika gigi hilang -->
          ${isHilang ? '<line x1="2" y1="2" x2="38" y2="38" stroke="#EF4444" stroke-width="3" /><line x1="38" y1="2" x2="2" y2="38" stroke="#EF4444" stroke-width="3" />' : ''}
        </svg>
      </div>
    `;
  }

  function renderLegend() {
    return `
      <div class="odontogram-legend">
        <div class="legend-item"><span class="legend-badge" style="background:#EF4444;"></span> Merah: Karies</div>
        <div class="legend-item"><span class="legend-badge" style="background:#10B981;"></span> Hijau: Tambalan</div>
        <div class="legend-item"><span class="legend-badge" style="background:#3B82F6;"></span> Biru: Saluran Akar</div>
        <div class="legend-item"><span class="legend-badge" style="background:#F59E0B;"></span> Oranye: Tambalan Sementara</div>
        <div class="legend-item"><span class="legend-badge" style="background:#8B7CF6;"></span> Ungu: Mahkota (Crown)</div>
        <div class="legend-item"><span class="legend-badge" style="background:#64748B;"></span> Silang: Gigi Hilang</div>
      </div>
    `;
  }

  function bindEvents(container) {
    if (isReadOnly) return;

    container.addEventListener('click', function(e) {
      const surface = e.target.closest('.surface-part');
      if (surface) {
        const toothNum = surface.dataset.tooth;
        const surfName = surface.dataset.surface;

        if (!toothState[toothNum]) {
          toothState[toothNum] = {};
        }

        if (currentCondition === 'hilang') {
          toothState[toothNum]['seluruh'] = 'hilang';
        } else if (currentCondition === 'sehat') {
          delete toothState[toothNum][surfName];
          delete toothState[toothNum]['seluruh'];
        } else {
          toothState[toothNum][surfName] = currentCondition;
          delete toothState[toothNum]['seluruh'];
        }

        render(container);

        // Update hidden input jika ada
        const stateInput = document.getElementById('odontogram-state-json');
        if (stateInput) {
          stateInput.value = JSON.stringify(toothState);
        }
      }
    });
  }

  function getState() {
    return toothState;
  }

  return {
    init,
    setCondition,
    getState
  };
})();

