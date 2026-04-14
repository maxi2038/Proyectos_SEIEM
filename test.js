// Colors for charts
  const COLORS = ["#7A3E4D", "#C3A04F", "#D4B76A", "#56212F", "#A8873A", "#F5F0E8", "#333333"];

  // Tab switching
  function showTab(id) {
    document.querySelectorAll('.tab-section').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-link').forEach(el => el.classList.remove('active'));
    document.getElementById('section-' + id).classList.add('active');
    document.getElementById('tab-' + id).classList.add('active');
  }

  // Chart helpers
  function createDonut(canvasId, data) {
    const el = document.getElementById(canvasId);
    if (!el || !data || data.length === 0) return;
    return new Chart(el, {
      type: 'doughnut',
      data: {
        labels: data.slice(0, 7).map(i => i._id || 'Sin Dato'),
        datasets: [{
          data: data.slice(0, 7).map(i => i.count),
          backgroundColor: COLORS,
          borderWidth: 0
        }]
      },
      options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });
  }

  function createBar(canvasId, data) {
    const el = document.getElementById(canvasId);
    if (!el || !data || data.length === 0) return;
    return new Chart(el, {
      type: 'bar',
      data: {
        labels: data.map(i => i._id || 'Sin Dato'),
        datasets: [{
          label: 'Total',
          data: data.map(i => i.count),
          backgroundColor: '#7A3E4D',
          borderRadius: 5
        }]
      },
      options: {
        indexAxis: 'y',
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { font: { size: 10 } } },
          y: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' } } }
        }
      }
    });
  }

  function fillTable(targetId, data) {
    const tbody = document.getElementById(targetId);
    if (!tbody || !data) return;
    tbody.innerHTML = '';
    data.slice(0, 7).forEach((item, idx) => {
      tbody.innerHTML += `<tr><td><span class="legend-dot" style="background:${COLORS[idx % COLORS.length]}"></span> ${item._id || 'Sin Dato'}</td><td style="text-align:right; font-weight:700;">${item.count}</td></tr>`;
    });
  }

  // Main init
  document.addEventListener('DOMContentLoaded', function () {
    console.log("Dashboard Loaded");
    try {
      // Direct raw JSON assignment to avoid any splitting issues
      const uM = {{ municipios_unete_json| safe
    }};
  const uN = [{"_id": "ATLACOMULCO", "count": 34}, {"_id": "TOLUCA", "count": 22}, {"_id": "NAUCALPAN", "count": 21}, {"_id": "ECATEPEC", "count": 13}, {"_id": "INDIGENA", "count": 10}, {"_id": "NEZAHUALCOYOTL", "count": 8}];
  const cM = [{"_id": "ECATEPEC DE MORELOS", "count": 76}, {"_id": "TOLUCA", "count": 38}, {"_id": "METEPEC", "count": 25}, {"_id": "TECAMAC", "count": 24}, {"_id": "TLALNEPANTLA DE BAZ", "count": 22}, {"_id": "NEZAHUALCOYOTL", "count": 13}, {"_id": "ATIZAPAN DE ZARAGOZA", "count": 12}, {"_id": "ALMOLOYA DE JUAREZ", "count": 11}, {"_id": "ZINACANTEPEC", "count": 9}, {"_id": "ZUMPANGO", "count": 8}, {"_id": "NAUCALPAN DE JUAREZ", "count": 8}, {"_id": "TENANGO DEL VALLE", "count": 7}, {"_id": "COACALCO DE BERRIOZABAL", "count": 7}, {"_id": "ACOLMAN", "count": 6}, {"_id": "LA PAZ", "count": 6}, {"_id": "CALIMAYA", "count": 5}, {"_id": "TULTITLAN", "count": 4}, {"_id": "NICOLAS ROMERO", "count": 4}, {"_id": "CUAUTITLAN IZCALLI", "count": 4}, {"_id": "CHAPULTEPEC", "count": 3}, {"_id": "TEOTIHUACAN", "count": 3}, {"_id": "CHIMALHUACAN", "count": 3}, {"_id": "HUIXQUILUCAN", "count": 3}, {"_id": "SAN MATEO ATENCO", "count": 3}, {"_id": "VALLE DE BRAVO", "count": 3}, {"_id": "MEXICALTZINGO", "count": 2}, {"_id": "JOCOTITLAN", "count": 2}, {"_id": "TENANCINGO", "count": 2}, {"_id": "LERMA", "count": 2}, {"_id": "IXTAPAN DE LA SAL", "count": 2}, {"_id": "ATLACOMULCO", "count": 2}, {"_id": "TEMOAYA", "count": 2}, {"_id": "CUAUTITLAN", "count": 2}, {"_id": "TEMASCALCINGO", "count": 2}, {"_id": "IXTLAHUACA", "count": 2}, {"_id": "NEXTLALPAN", "count": 2}, {"_id": "TEJUPILCO", "count": 1}, {"_id": "TLALMANALCO", "count": 1}, {"_id": "JIQUIPILCO", "count": 1}, {"_id": "ACAMBAY", "count": 1}, {"_id": "DONATO GUERRA", "count": 1}, {"_id": "CHIAUTLA", "count": 1}, {"_id": "CHALCO", "count": 1}, {"_id": "TEQUIXQUIAC", "count": 1}, {"_id": "TEXCOCO", "count": 1}, {"_id": "OTZOLOTEPEC", "count": 1}, {"_id": "JUCHITEPEC", "count": 1}, {"_id": "CHAPA DE MOTA", "count": 1}, {"_id": "CHICONCUAC", "count": 1}, {"_id": "HUEHUETOCA", "count": 1}, {"_id": "ATLAUTLA", "count": 1}, {"_id": "TEMASCALAPA", "count": 1}, {"_id": "SAN FELIPE DEL PROGRESO", "count": 1}, {"_id": "TONANITLA", "count": 1}, {"_id": "PAPALOTLA", "count": 1}, {"_id": "MELCHOR OCAMPO", "count": 1}, {"_id": "OCOYOACAC", "count": 1}, {"_id": "TIANGUISTENCO", "count": 1}, {"_id": "SAN ANTONIO LA ISLA", "count": 1}, {"_id": "JILOTEPEC", "count": 1}, {"_id": "TULTEPEC", "count": 1}, {"_id": "XALATLACO", "count": 1}, {"_id": "HUEYPOXTLA", "count": 1}, {"_id": "OCUILAN", "count": 1}, {"_id": "VILLA VICTORIA", "count": 1}];
  const cN = [{"_id": "ECATEPEC", "count": 124}, {"_id": "TOLUCA", "count": 48}, {"_id": "PREESCOLAR V.T.", "count": 47}, {"_id": "NAUCALPAN", "count": 39}, {"_id": "ESPECIAL V.M.", "count": 24}, {"_id": "NEZAHUALCOYOTL", "count": 19}, {"_id": "ESPECIAL V.T.", "count": 17}, {"_id": "ATLACOMULCO", "count": 13}, {"_id": "INDIGENA", "count": 10}, {"_id": "PREESCOLAR V.M.", "count": 10}, {"_id": "ADULTOS", "count": 7}];
  const fM = [{"_id": "ECATEPEC DE MORELOS", "count": 385}, {"_id": "TOLUCA", "count": 213}, {"_id": "NEZAHUALCOYOTL", "count": 199}, {"_id": "TLALNEPANTLA DE BAZ", "count": 183}, {"_id": "SAN FELIPE DEL PROGRESO", "count": 158}, {"_id": "NAUCALPAN DE JUAREZ", "count": 153}, {"_id": "TECAMAC", "count": 133}, {"_id": "IXTLAHUACA", "count": 107}, {"_id": "ALMOLOYA DE JUAREZ", "count": 104}, {"_id": "NICOLAS ROMERO", "count": 97}, {"_id": "TEXCOCO", "count": 96}, {"_id": "ATIZAPAN DE ZARAGOZA", "count": 95}, {"_id": "JILOTEPEC", "count": 93}, {"_id": "SAN JOSE DEL RINCON", "count": 92}, {"_id": "VILLA VICTORIA", "count": 89}, {"_id": "CUAUTITLAN IZCALLI", "count": 83}, {"_id": "ATLACOMULCO", "count": 80}, {"_id": "LERMA", "count": 78}, {"_id": "CHALCO", "count": 77}, {"_id": "ZINACANTEPEC", "count": 76}, {"_id": "TENANCINGO", "count": 71}, {"_id": "CHIMALHUACAN", "count": 71}, {"_id": "ZUMPANGO", "count": 71}, {"_id": "JIQUIPILCO", "count": 70}, {"_id": "TEMASCALCINGO", "count": 69}, {"_id": "IXTAPALUCA", "count": 68}, {"_id": "TULTITLAN", "count": 67}, {"_id": "TEMOAYA", "count": 66}, {"_id": "VILLA DE ALLENDE", "count": 65}, {"_id": "ACULCO", "count": 64}, {"_id": "HUIXQUILUCAN", "count": 61}, {"_id": "ACAMBAY", "count": 61}, {"_id": "VALLE DE BRAVO", "count": 54}, {"_id": "TEJUPILCO", "count": 54}, {"_id": "HUEHUETOCA", "count": 48}, {"_id": "METEPEC", "count": 48}, {"_id": "COACALCO DE BERRIOZABAL", "count": 48}, {"_id": "VALLE DE CHALCO SOLIDARIDAD", "count": 47}, {"_id": "EL ORO", "count": 45}, {"_id": "TEXCALTITLAN", "count": 44}, {"_id": "JOCOTITLAN", "count": 44}, {"_id": "SULTEPEC", "count": 43}, {"_id": "TENANGO DEL VALLE", "count": 43}, {"_id": "CUAUTITLAN", "count": 43}, {"_id": "DONATO GUERRA", "count": 42}, {"_id": "COATEPEC HARINAS", "count": 42}, {"_id": "MORELOS", "count": 39}, {"_id": "TLATLAYA", "count": 39}, {"_id": "OTZOLOTEPEC", "count": 38}, {"_id": "VILLA DEL CARBON", "count": 36}, {"_id": "ACOLMAN", "count": 35}, {"_id": "LA PAZ", "count": 34}, {"_id": "TIANGUISTENCO", "count": 34}, {"_id": "AMATEPEC", "count": 33}, {"_id": "IXTAPAN DE LA SAL", "count": 33}, {"_id": "CHAPA DE MOTA", "count": 32}, {"_id": "OCUILAN", "count": 29}, {"_id": "CALIMAYA", "count": 28}, {"_id": "MALINALCO", "count": 27}, {"_id": "TEPOTZOTLAN", "count": 26}, {"_id": "VILLA GUERRERO", "count": 26}, {"_id": "AMANALCO", "count": 24}, {"_id": "SAN MATEO ATENCO", "count": 24}, {"_id": "AXAPUSCO", "count": 24}, {"_id": "POLOTITLAN", "count": 23}, {"_id": "ZUMPAHUACAN", "count": 23}, {"_id": "OCOYOACAC", "count": 23}, {"_id": "TULTEPEC", "count": 22}, {"_id": "CHICOLOAPAN", "count": 22}, {"_id": "TEMASCALTEPEC", "count": 21}, {"_id": "TEOTIHUACAN", "count": 21}, {"_id": "ALMOLOYA DE ALQUISIRAS", "count": 21}, {"_id": "HUEYPOXTLA", "count": 21}, {"_id": "MELCHOR OCAMPO", "count": 19}, {"_id": "TEOLOYUCAN", "count": 16}, {"_id": "SOYANIQUILPAN DE JUAREZ", "count": 14}, {"_id": "TEMASCALAPA", "count": 14}, {"_id": "ZACUALPAN", "count": 14}, {"_id": "TLALMANALCO", "count": 13}, {"_id": "IXTAPAN DEL ORO", "count": 13}, {"_id": "ATENCO", "count": 12}, {"_id": "LUVIANOS", "count": 11}, {"_id": "TEZOYUCA", "count": 11}, {"_id": "OTUMBA", "count": 10}, {"_id": "SANTO TOMAS", "count": 10}, {"_id": "XONACATLAN", "count": 10}, {"_id": "TIMILPAN", "count": 9}, {"_id": "AMECAMECA", "count": 9}, {"_id": "COYOTEPEC", "count": 9}, {"_id": "NEXTLALPAN", "count": 9}, {"_id": "CAPULHUAC", "count": 8}, {"_id": "TEQUIXQUIAC", "count": 8}, {"_id": "CHIAUTLA", "count": 8}, {"_id": "JUCHITEPEC", "count": 7}, {"_id": "CHAPULTEPEC", "count": 7}, {"_id": "TEPETLIXPA", "count": 7}, {"_id": "SAN MARTIN DE LAS PIRAMIDES", "count": 7}, {"_id": "CHICONCUAC", "count": 6}, {"_id": "XALATLACO", "count": 6}, {"_id": "TEMAMATLA", "count": 6}, {"_id": "OTZOLOAPAN", "count": 6}, {"_id": "TENANGO DEL AIRE", "count": 6}, {"_id": "TONATICO", "count": 5}, {"_id": "ATLAUTLA", "count": 5}, {"_id": "MEXICALTZINGO", "count": 5}, {"_id": "SAN ANTONIO LA ISLA", "count": 5}, {"_id": "JILOTZINGO", "count": 5}, {"_id": "TONANITLA", "count": 4}, {"_id": "JALTENCO", "count": 4}, {"_id": "OZUMBA", "count": 4}, {"_id": "APAXCO", "count": 4}, {"_id": "PAPALOTLA", "count": 4}, {"_id": "AYAPANGO", "count": 4}, {"_id": "TEPETLAOXTOC", "count": 3}, {"_id": "ATIZAPAN", "count": 3}, {"_id": "RAYON", "count": 3}, {"_id": "ISIDRO FABELA", "count": 2}, {"_id": "SAN SIMON DE GUERRERO", "count": 2}, {"_id": "ECATZINGO", "count": 2}, {"_id": "JOQUICINGO", "count": 2}, {"_id": "ZACAZONAPAN", "count": 2}, {"_id": "ALMOLOYA DEL RIO", "count": 2}, {"_id": "NOPALTEPEC", "count": 1}];
  const fN = [{"_id": "PREESCOLAR V.T.", "count": 914}, {"_id": "PREESCOLAR V.M.", "count": 726}, {"_id": "ATLACOMULCO", "count": 652}, {"_id": "INDIGENA", "count": 551}, {"_id": "ECATEPEC", "count": 548}, {"_id": "TOLUCA", "count": 528}, {"_id": "NAUCALPAN", "count": 512}, {"_id": "NEZAHUALCOYOTL", "count": 417}, {"_id": "ESPECIAL V.M.", "count": 153}, {"_id": "ESPECIAL V.T.", "count": 93}, {"_id": "ADULTOS", "count": 50}, {"_id": "INICIAL", "count": 10}];

  // UNETE
  createDonut('chartUnm', uM);
  createBar('chartUnn', uN);
  fillTable('table-unm', uM);

  // CFE
  createDonut('chartCfeM', cM);
  createBar('chartCfeN', cN);
  fillTable('table-cfe-m', cM);

  // Federales
  createDonut('chartFedM', fM);
  createBar('chartFedN', fN);
  fillTable('table-fed-m', fM);
    } catch (err) {
    console.error("Chart rendering error:", err);
  }
  });
