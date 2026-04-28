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

// Star rating — render star HTML from a decimal value (shared helper)
function renderStarsHtml(nota) {
    var html = '';
    for (var i = 1; i <= 5; i++) {
        if (nota >= i)
            html += '<span style="color:#f39c12;">★</span>';
        else if (nota >= i - 0.5)
            html += '<span style="background:linear-gradient(to right,#f39c12 50%,#ddd 50%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">★</span>';
        else
            html += '<span style="color:#ddd;">★</span>';
    }
    return html;
}

// Modal — index.php tool detail popup with image gallery
var modalOverlay = document.getElementById('modalOverlay');
if (modalOverlay) {
    document.querySelectorAll('.btn-ver-mais').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('modalNome').textContent = btn.dataset.nome;
            document.getElementById('modalCategoria').textContent = btn.dataset.categoria;
            document.getElementById('modalDescricao').textContent = btn.dataset.descricao || 'Sem descrição disponível.';
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

            // Rating row
            var avgNota = parseFloat(btn.dataset.avgNota);
            var totalAvaliacoes = parseInt(btn.dataset.totalAvaliacoes) || 0;
            var ratingRow = document.getElementById('modalRatingRow');
            if (ratingRow) {
                if (!isNaN(avgNota) && totalAvaliacoes > 0) {
                    document.getElementById('modalRatingStars').innerHTML = renderStarsHtml(avgNota);
                    document.getElementById('modalRatingCount').textContent = avgNota.toFixed(1) + ' (' + totalAvaliacoes + ' avalia' + (totalAvaliacoes > 1 ? 'ções' : 'ção') + ')';
                    ratingRow.style.display = '';
                } else {
                    ratingRow.style.display = 'none';
                }
            }

            // Discount row
            var descontoDias = parseInt(btn.dataset.descontoDias);
            var precoDesconto = parseFloat(btn.dataset.precoDesconto);
            var descontoRow = document.getElementById('modalDescontoRow');
            if (descontoRow) {
                if (descontoDias && precoDesconto) {
                    document.getElementById('modalDescontoTxt').textContent = 'A partir de ' + descontoDias + ' dias: ' + precoDesconto.toFixed(2) + '€/dia';
                    descontoRow.style.display = '';
                } else {
                    descontoRow.style.display = 'none';
                }
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

// Opens the tool detail modal — called from map popups and btn-ver-mais buttons
window.abrirModalFerramenta = function (id) {
    var f = (window.ferramentasGeo || []).find(function (x) { return x.fer_id == id; });
    if (!f) return;

    document.getElementById('modalNome').textContent      = f.fer_nome;
    document.getElementById('modalCategoria').textContent = f.cat_nome;
    document.getElementById('modalDescricao').textContent = f.fer_descricao || 'Sem descrição disponível.';
    document.getElementById('modalPrecoBase').textContent = parseFloat(f.fer_preco_base).toFixed(2);

    var alugarLink   = document.getElementById('modalAlugarLink');
    var indisponivel = document.getElementById('modalIndisponivel');
    alugarLink.style.display   = '';
    indisponivel.style.display = f.ocupada == 1 ? '' : 'none';
    alugarLink.href = 'alugarferramenta.php?id=' + f.fer_id;

    var imagens = f.img_principal ? [f.img_principal] : [];
    var galeria  = document.getElementById('modalGaleria');
    var imgMain  = document.getElementById('modalImgMain');
    var thumbsEl = document.getElementById('modalImgThumbs');

    if (imagens.length > 0) {
        galeria.style.display = '';
        imgMain.src = imagens[0];
        thumbsEl.innerHTML = '';
    } else {
        galeria.style.display = 'none';
    }

    // Rating row
    var ratingRow = document.getElementById('modalRatingRow');
    if (ratingRow) {
        var avgNota = parseFloat(f.avg_nota_fer);
        var totalAv = parseInt(f.total_avaliacoes) || 0;
        if (!isNaN(avgNota) && totalAv > 0) {
            document.getElementById('modalRatingStars').innerHTML = renderStarsHtml(avgNota);
            document.getElementById('modalRatingCount').textContent = avgNota.toFixed(1) + ' (' + totalAv + ' avalia' + (totalAv > 1 ? 'ções' : 'ção') + ')';
            ratingRow.style.display = '';
        } else {
            ratingRow.style.display = 'none';
        }
    }

    // Discount row
    var descontoRow = document.getElementById('modalDescontoRow');
    if (descontoRow) {
        var dd = parseInt(f.fer_desconto_dias);
        var pd = parseFloat(f.fer_preco_desconto);
        if (dd && pd) {
            document.getElementById('modalDescontoTxt').textContent = 'A partir de ' + dd + ' dias: ' + pd.toFixed(2) + '€/dia';
            descontoRow.style.display = '';
        } else {
            descontoRow.style.display = 'none';
        }
    }

    document.getElementById('modalOverlay').classList.add('active');
};

// Home page map — reads tool data set by index.php via window.ferramentasGeo
if (typeof L !== 'undefined' && window.ferramentasGeo && document.querySelector('.map-section #mapa')) {
    var homeMap = L.map('mapa').setView([38.72, -9.14], 10);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(homeMap);
    window.ferramentasGeo.forEach(function (f) {
        var popup = '<b>' + f.fer_nome + '</b><br>' +
            f.cat_nome + ' · ' + parseFloat(f.fer_preco).toFixed(2) + '€/dia<br>' +
            '<button class="map-popup-btn" onclick="abrirModalFerramenta(' + f.fer_id + ')">Ver mais</button>';
        L.marker([parseFloat(f.fer_lat), parseFloat(f.fer_lng)])
            .addTo(homeMap)
            .bindPopup(popup, { minWidth: 180 });
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

// Rental page map — shows tool pin; div data-lat/lng set in HTML by alugarferramenta.php
if (typeof L !== 'undefined' && document.getElementById('mapaFerramenta')) {
    var rentalMapDiv = document.getElementById('mapaFerramenta');
    var fLat = parseFloat(rentalMapDiv.dataset.lat);
    var fLng = parseFloat(rentalMapDiv.dataset.lng);
    var rentalMap = L.map('mapaFerramenta', { zoomControl: true, scrollWheelZoom: false }).setView([fLat, fLng], 14);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(rentalMap);
    L.marker([fLat, fLng]).addTo(rentalMap);
}

// Rental page calendar — reads window.aluguerData set by alugarferramenta.php
if (typeof flatpickr !== 'undefined' && window.aluguerData && document.getElementById('calendarContainer')) {
    var inicioInp = document.getElementById('inicio');
    var fimInp    = document.getElementById('fim');
    var resumo    = document.getElementById('resumoPreco');
    var totalEl   = document.getElementById('totalPreco');
    var defaultDates = (inicioInp.value && fimInp.value) ? [inicioInp.value, fimInp.value] : [];
    flatpickr('#calendarContainer', {
        mode:        'range',
        inline:      true,
        minDate:     'today',
        locale:      'pt',
        disable:     window.aluguerData.bookedRanges,
        dateFormat:  'Y-m-d',
        defaultDate: defaultDates,
        onChange: function (selectedDates) {
            var descAplic = document.getElementById('descontoAplicado');
            if (selectedDates.length === 2) {
                var fmt = function (d) {
                    return d.getFullYear() + '-' +
                           String(d.getMonth() + 1).padStart(2, '0') + '-' +
                           String(d.getDate()).padStart(2, '0');
                };
                inicioInp.value = fmt(selectedDates[0]);
                fimInp.value    = fmt(selectedDates[1]);
                var dias = Math.round((selectedDates[1] - selectedDates[0]) / 86400000);
                if (dias > 0) {
                    var d = window.aluguerData;
                    var temDesconto = d.descontoDias && d.precoDesconto && dias >= d.descontoDias;
                    var precoDia = temDesconto ? d.precoDesconto : d.precoDia;
                    totalEl.textContent = (dias * precoDia).toFixed(2) + '€ (' +
                        dias + ' dia' + (dias > 1 ? 's' : '') + ' × ' + precoDia.toFixed(2) + '€)';
                    resumo.style.display = 'block';
                    if (descAplic) {
                        if (temDesconto) {
                            descAplic.textContent = 'Desconto aplicado: ' + precoDia.toFixed(2) + '€/dia (a partir de ' + d.descontoDias + ' dias)';
                            descAplic.style.display = 'block';
                        } else {
                            descAplic.style.display = 'none';
                        }
                    }
                } else {
                    resumo.style.display = 'none';
                    if (descAplic) descAplic.style.display = 'none';
                }
            } else {
                inicioInp.value = '';
                fimInp.value    = '';
                resumo.style.display = 'none';
                if (descAplic) descAplic.style.display = 'none';
            }
        }
    });
}

// Edit tool page — existing photos management; reads window.existingPhotos set by editarferramenta.php
if (window.existingPhotos) {
    (function () {
        var existingPhotos = window.existingPhotos;
        var grid         = document.getElementById('fotoExistingGrid');
        var deleteCont   = document.getElementById('deleteImgsContainer');
        var principalInp = document.getElementById('imgPrincipalId');

        if (!grid) return;

        var deletedIds = [];

        function renderExisting() {
            grid.innerHTML = '';
            deleteCont.innerHTML = '';

            deletedIds.forEach(function (did) {
                var inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = 'delete_imgs[]';
                inp.value = did;
                deleteCont.appendChild(inp);
            });

            var p = existingPhotos.find(function (x) { return x.principal; });
            principalInp.value = p ? p.id : '';

            existingPhotos.forEach(function (photo, idx) {
                var item = document.createElement('div');
                item.className = 'foto-preview-item' + (photo.principal ? ' principal' : '');

                var img = document.createElement('img');
                img.src = photo.path;
                img.alt = '';
                item.appendChild(img);

                if (photo.principal) {
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
                            existingPhotos.forEach(function (p) { p.principal = false; });
                            existingPhotos[i].principal = true;
                            renderExisting();
                        });
                    })(idx);
                    item.appendChild(overlay);
                }

                var btnX = document.createElement('button');
                btnX.type = 'button';
                btnX.className = 'foto-btn-remove';
                btnX.textContent = '×';
                (function (i, ph) {
                    btnX.addEventListener('click', function () {
                        deletedIds.push(ph.id);
                        existingPhotos.splice(i, 1);
                        if (existingPhotos.length > 0 && !existingPhotos.some(function (p) { return p.principal; })) {
                            existingPhotos[0].principal = true;
                        }
                        renderExisting();
                    });
                })(idx, photo);
                item.appendChild(btnX);

                grid.appendChild(item);
            });
        }

        renderExisting();
    })();
}

// Profile page — avatar upload
var avatarClick = document.getElementById('avatarClick');
if (avatarClick) {
    var avatarInput = document.getElementById('avatarInput');
    avatarClick.addEventListener('click', function () { avatarInput.click(); });
    avatarInput.addEventListener('change', function () {
        if (!this.files[0]) return;
        var fd = new FormData();
        fd.append('foto', this.files[0]);
        fetch('uploadfoto.php', { method: 'POST', body: fd })
            .then(function (r) {
                if (!r.ok) throw new Error('upload_failed');
                return r.json();
            })
            .then(function (data) {
                if (!data.path) throw new Error('no_path');
                var existing = document.getElementById('avatarImg');
                var svg = document.getElementById('avatarSvg');
                if (existing) {
                    existing.src = data.path + '?t=' + Date.now();
                } else {
                    if (svg) svg.remove();
                    var img = document.createElement('img');
                    img.id = 'avatarImg';
                    img.alt = 'Foto de perfil';
                    img.src = data.path;
                    avatarClick.insertBefore(img, avatarClick.firstChild);
                }
                var circle = document.querySelector('.profile-circle');
                if (circle) {
                    circle.style.backgroundImage = 'url(' + data.path + '?t=' + Date.now() + ')';
                    circle.style.backgroundSize  = 'cover';
                    circle.style.backgroundColor = 'transparent';
                }
            })
            .catch(function () {
                alert('Erro ao carregar a imagem. Verifica o formato e tenta novamente.');
            });
    });
}

// Star rating — interactive picker
// Uses input.value as source of truth so pickers can be reset externally (e.g. modal reopen).
document.querySelectorAll('.star-picker').forEach(function (picker) {
    var stars = Array.from(picker.querySelectorAll('.sp-star'));
    var input = picker.querySelector('input[type="hidden"]');

    function setStars(val) {
        stars.forEach(function (s, i) {
            s.className = 'sp-star';
            if (val >= i + 1)        s.classList.add('full');
            else if (val >= i + 0.5) s.classList.add('half');
        });
    }

    stars.forEach(function (star, i) {
        star.addEventListener('mousemove', function (e) {
            var rect = star.getBoundingClientRect();
            setStars(e.clientX < rect.left + rect.width / 2 ? i + 0.5 : i + 1);
        });
        star.addEventListener('click', function (e) {
            var rect = star.getBoundingClientRect();
            input.value = e.clientX < rect.left + rect.width / 2 ? i + 0.5 : i + 1;
            setStars(parseFloat(input.value));
        });
    });

    picker.addEventListener('mouseleave', function () { setStars(parseFloat(input.value) || 0); });

    var form = picker.closest('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!parseFloat(input.value)) {
                e.preventDefault();
                picker.style.outline = '2px solid #c0392b';
                picker.style.borderRadius = '2px';
                setTimeout(function () { picker.style.outline = ''; }, 900);
            }
        });
    }
});

// Star rating — read-only display
document.querySelectorAll('.stars-display').forEach(function (el) {
    var nota = parseFloat(el.dataset.nota);
    var html = '';
    for (var i = 1; i <= 5; i++) {
        if (nota >= i)
            html += '<span style="color:#f39c12;">★</span>';
        else if (nota >= i - 0.5)
            html += '<span style="background:linear-gradient(to right,#f39c12 50%,#ddd 50%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">★</span>';
        else
            html += '<span style="color:#ddd;">★</span>';
    }
    el.innerHTML = html;
});

// Rating modal — opens from .btn-avaliar buttons in dashboard history table
var ratingModalOverlay = document.getElementById('ratingModalOverlay');
if (ratingModalOverlay) {
    document.querySelectorAll('.btn-avaliar').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('ratingAluId').value = btn.dataset.aluId;
            ratingModalOverlay.querySelectorAll('.star-picker').forEach(function (picker) {
                picker.querySelector('input[type="hidden"]').value = '';
                picker.querySelectorAll('.sp-star').forEach(function (s) { s.className = 'sp-star'; });
            });
            var ta = ratingModalOverlay.querySelector('textarea');
            if (ta) ta.value = '';
            ratingModalOverlay.classList.add('active');
        });
    });

    document.getElementById('ratingModalClose').addEventListener('click', function () {
        ratingModalOverlay.classList.remove('active');
    });
    ratingModalOverlay.addEventListener('click', function (e) {
        if (e.target === ratingModalOverlay) ratingModalOverlay.classList.remove('active');
    });
}

// Profile page — two-click delete confirmation
document.querySelectorAll('.delete-tool-form').forEach(function (form) {
    var btn = form.querySelector('.btn-delete-tool');
    var confirmed = false;
    form.addEventListener('submit', function (e) {
        if (!confirmed) {
            e.preventDefault();
            confirmed = true;
            btn.textContent = 'Tens a certeza?';
            btn.style.background = '#7b1a1a';
            setTimeout(function () {
                confirmed = false;
                btn.textContent = 'Apagar';
                btn.style.background = '#c0392b';
            }, 3000);
        }
    });
});
