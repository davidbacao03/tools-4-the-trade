// Search — live filter of .tool-card elements
var searchInput = document.querySelector('.search-box input');
if (searchInput) {
    var searchEmptyMsg = null;
    searchInput.addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        var anyVisible = false;
        document.querySelectorAll('.tool-card').forEach(function (card) {
            var visible = !q || card.textContent.toLowerCase().includes(q);
            card.style.display = visible ? '' : 'none';
            if (visible) anyVisible = true;
        });
        var toolsGrid = document.querySelector('.tools-grid');
        if (toolsGrid) {
            if (!anyVisible && q) {
                if (!searchEmptyMsg) {
                    searchEmptyMsg = document.createElement('p');
                    searchEmptyMsg.className = 'empty-msg';
                    searchEmptyMsg.id = 'searchEmptyMsg';
                    toolsGrid.appendChild(searchEmptyMsg);
                }
                searchEmptyMsg.textContent = 'Nenhuma ferramenta encontrada para "' + q + '".';
                searchEmptyMsg.style.display = '';
            } else if (searchEmptyMsg) {
                searchEmptyMsg.style.display = 'none';
            }
        }
    });
}

// Geolocation — sort cards by distance and show distance badge
function haversine(lat1, lon1, lat2, lon2) {
    var R = 6371;
    var dLat = (lat2 - lat1) * Math.PI / 180;
    var dLon = (lon2 - lon1) * Math.PI / 180;
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
          + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180)
          * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function formatDist(km) {
    return km < 1 ? Math.round(km * 1000) + ' m' : km.toFixed(1) + ' km';
}

var grid = document.querySelector('.tools-grid');
if (grid && navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        function (pos) {
            var uLat = pos.coords.latitude;
            var uLng = pos.coords.longitude;
            var cards = Array.from(grid.querySelectorAll('.tool-card'));

            cards.forEach(function (card) {
                var lat = parseFloat(card.dataset.lat);
                var lng = parseFloat(card.dataset.lng);
                if (!isNaN(lat) && !isNaN(lng)) {
                    var dist = haversine(uLat, uLng, lat, lng);
                    card.dataset.dist = dist;
                    var badge = document.createElement('p');
                    badge.className = 'dist-badge';
                    badge.textContent = formatDist(dist);
                    var ref = card.querySelector('button, a.simple-button, span.badge-indisponivel');
                    card.insertBefore(badge, ref || null);
                } else {
                    card.dataset.dist = 999999;
                }
            });

            cards.sort(function (a, b) {
                return parseFloat(a.dataset.dist || 999999) - parseFloat(b.dataset.dist || 999999);
            });
            cards.forEach(function (c) { grid.appendChild(c); });
        },
        function (err) {
            console.warn('Geolocalização não disponível:', err.message);
        },
        { timeout: 8000, maximumAge: 60000 }
    );
}

// Modal — index.php tool detail popup with image gallery
var modalOverlay = document.getElementById('modalOverlay');
if (modalOverlay) {
    document.querySelectorAll('.btn-ver-mais').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('modalNome').textContent = btn.dataset.nome;
            document.getElementById('modalCategoria').textContent = btn.dataset.categoria;
            document.getElementById('modalDescricao').textContent = btn.dataset.descricao || 'Sem descrição disponível.';
            document.getElementById('modalPreco').textContent = btn.dataset.preco;
            document.getElementById('modalPrecoBase').textContent = btn.dataset.precoBase;

            var ocupada = btn.dataset.ocupada === '1';
            var alugarLink = document.getElementById('modalAlugarLink');
            var indisponivel = document.getElementById('modalIndisponivel');
            alugarLink.style.display = '';
            indisponivel.style.display = ocupada ? '' : 'none';
            alugarLink.href = 'alugarferramenta.php?id=' + btn.dataset.id;

            // Gallery
            var imagens = [];
            try { imagens = JSON.parse(btn.dataset.imagens || '[]'); } catch(e) {}
            var galeria = document.getElementById('modalGaleria');
            var imgMain = document.getElementById('modalImgMain');
            var thumbsEl = document.getElementById('modalImgThumbs');

            if (imagens.length > 0) {
                galeria.style.display = '';
                imgMain.src = imagens[0];
                thumbsEl.innerHTML = '';
                imagens.forEach(function (src, i) {
                    var thumb = document.createElement('img');
                    thumb.src = src;
                    thumb.className = 'modal-thumb' + (i === 0 ? ' active' : '');
                    thumb.addEventListener('click', function () {
                        imgMain.src = src;
                        thumbsEl.querySelectorAll('.modal-thumb').forEach(function (t) { t.classList.remove('active'); });
                        thumb.classList.add('active');
                    });
                    thumbsEl.appendChild(thumb);
                });
            } else {
                galeria.style.display = 'none';
            }

            modalOverlay.classList.add('active');
        });
    });

    document.getElementById('modalClose').addEventListener('click', function () {
        modalOverlay.classList.remove('active');
    });
    modalOverlay.addEventListener('click', function (e) {
        if (e.target === modalOverlay) modalOverlay.classList.remove('active');
    });
}

// Home page map — reads tool data set by index.php via window.ferramentasGeo
if (typeof L !== 'undefined' && window.ferramentasGeo && document.querySelector('.map-section #mapa')) {
    var homeMap = L.map('mapa').setView([39.5, -8.0], 7);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(homeMap);
    window.ferramentasGeo.forEach(function (f) {
        L.marker([parseFloat(f.fer_lat), parseFloat(f.fer_lng)])
            .addTo(homeMap)
            .bindPopup(
                '<b>' + f.fer_nome + '</b><br>' +
                'Categoria: ' + f.cat_nome + '<br>' +
                'Preço: ' + f.fer_preco + '€/dia'
            );
    });
}

// Add/edit tool form map — click to pick location; pre-loads existing coords if present
if (typeof L !== 'undefined' && document.querySelector('.form-section #mapa')) {
    var mapDiv  = document.querySelector('.form-section #mapa');
    var latInput = document.getElementById('lat');
    var lngInput = document.getElementById('lng');
    document.querySelector('label[for="lat"]').style.display = 'none';
    latInput.style.display = 'none';
    document.querySelector('label[for="lng"]').style.display = 'none';
    lngInput.style.display = 'none';

    var existLat = mapDiv.dataset.lat ? parseFloat(mapDiv.dataset.lat) : null;
    var existLng = mapDiv.dataset.lng ? parseFloat(mapDiv.dataset.lng) : null;

    var formMap = L.map('mapa').setView([39.5, -8.0], 7);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(formMap);

    var marcador = null;
    if (existLat && existLng) {
        formMap.setView([existLat, existLng], 13);
        marcador = L.marker([existLat, existLng]).addTo(formMap).bindPopup('Localização da ferramenta').openPopup();
    } else {
        formMap.locate({ setView: true, maxZoom: 14 });
    }

    formMap.on('click', function (e) {
        latInput.value = e.latlng.lat.toFixed(7);
        lngInput.value = e.latlng.lng.toFixed(7);
        document.getElementById('mapa-info').textContent =
            'Localização selecionada: ' + e.latlng.lat.toFixed(7) + ', ' + e.latlng.lng.toFixed(7);
        if (marcador) { marcador.setLatLng(e.latlng); }
        else { marcador = L.marker(e.latlng).addTo(formMap).bindPopup('Localização da ferramenta').openPopup(); }
    });
}

// Rental date calculator — reads price from data-preco on .tool-form
var rentalForm = document.querySelector('.tool-form[data-preco]');
if (rentalForm) {
    var precoDia = parseFloat(rentalForm.dataset.preco);
    var inicioInput = document.getElementById('inicio');
    var fimInput = document.getElementById('fim');
    var resumo = document.getElementById('resumoPreco');
    var totalEl = document.getElementById('totalPreco');

    function calcular() {
        if (!inicioInput.value || !fimInput.value) { resumo.style.display = 'none'; return; }
        var dias = Math.round((new Date(fimInput.value) - new Date(inicioInput.value)) / 86400000);
        if (dias <= 0) { resumo.style.display = 'none'; return; }
        totalEl.textContent = (dias * precoDia).toFixed(2) + '€ (' + dias + ' dia' + (dias > 1 ? 's' : '') + ')';
        resumo.style.display = 'block';
    }

    inicioInput.addEventListener('change', function () {
        if (fimInput.value && fimInput.value <= inicioInput.value) fimInput.value = '';
        fimInput.min = inicioInput.value;
        calcular();
    });
    fimInput.addEventListener('change', calcular);
    calcular();
}

// Custom photo uploader — adicionarferramentas.php
var fotoDropZone = document.getElementById('fotoDropZone');
if (fotoDropZone) {
    var fotoInput  = document.getElementById('imagens');
    var fotoGrid   = document.getElementById('fotoPreviewGrid');
    var fotoFiles  = [];

    function fotoRender() {
        fotoGrid.innerHTML = '';
        fotoFiles.forEach(function (file, idx) {
            var url  = URL.createObjectURL(file);
            var item = document.createElement('div');
            item.className = 'foto-preview-item' + (idx === 0 ? ' principal' : '');

            var img = document.createElement('img');
            img.src = url;
            img.alt = '';
            item.appendChild(img);

            if (idx === 0) {
                var badge = document.createElement('div');
                badge.className = 'foto-badge-principal';
                badge.textContent = 'Principal';
                item.appendChild(badge);
            } else {
                var overlay = document.createElement('div');
                overlay.className = 'foto-overlay-principal';
                overlay.textContent = 'Tornar principal';
                (function (i) {
                    overlay.addEventListener('click', function () {
                        fotoFiles.unshift(fotoFiles.splice(i, 1)[0]);
                        fotoRender();
                    });
                })(idx);
                item.appendChild(overlay);
            }

            var btnX = document.createElement('button');
            btnX.type = 'button';
            btnX.className = 'foto-btn-remove';
            btnX.textContent = '×';
            (function (i) {
                btnX.addEventListener('click', function () {
                    fotoFiles.splice(i, 1);
                    fotoRender();
                });
            })(idx);
            item.appendChild(btnX);

            fotoGrid.appendChild(item);
        });
    }

    function fotoAdd(newFiles) {
        Array.from(newFiles).forEach(function (f) {
            if (f.type.startsWith('image/')) fotoFiles.push(f);
        });
        fotoRender();
    }

    fotoDropZone.addEventListener('click', function () { fotoInput.click(); });
    fotoInput.addEventListener('change', function () { fotoAdd(this.files); this.value = ''; });

    fotoDropZone.addEventListener('dragover',  function (e) { e.preventDefault(); fotoDropZone.classList.add('dragover'); });
    fotoDropZone.addEventListener('dragleave', function ()  { fotoDropZone.classList.remove('dragover'); });
    fotoDropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        fotoDropZone.classList.remove('dragover');
        fotoAdd(e.dataTransfer.files);
    });

    fotoDropZone.closest('form').addEventListener('submit', function (e) {
        e.preventDefault();
        var form = this;
        var redirectUrl = form.dataset.redirect || 'Ferramentas.php';
        var fd = new FormData(form);
        fd.delete('imagens[]');
        fotoFiles.forEach(function (f) { fd.append('imagens[]', f); });
        fetch(window.location.href, { method: 'POST', body: fd })
            .finally(function () { window.location.href = redirectUrl; });
    });
}

// Alugar page gallery — thumbnail click switches main image
var galeriaMain = document.getElementById('galeriaMain');
if (galeriaMain) {
    document.querySelectorAll('.galeria-thumb').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            galeriaMain.src = thumb.dataset.full || thumb.src;
            document.querySelectorAll('.galeria-thumb').forEach(function (t) { t.classList.remove('active'); });
            thumb.classList.add('active');
        });
    });
}
