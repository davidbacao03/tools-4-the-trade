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
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-ver-mais');
        if (!btn) return;
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

            // Owner card
            var donoCard = document.getElementById('modalDonoCard');
            if (donoCard) {
                var donoNome = btn.dataset.donoNome;
                if (donoNome) {
                    document.getElementById('modalDonoNome').textContent = donoNome;
                    var donoAvatar = document.getElementById('modalDonoAvatar');
                    var donoFoto = btn.dataset.donoFoto;
                    donoAvatar.style.backgroundImage = donoFoto ? "url('" + donoFoto + "')" : '';
                    donoAvatar.classList.toggle('dono-avatar-empty', !donoFoto);
                    var avgNotaDono = parseFloat(btn.dataset.avgNotaDono);
                    document.getElementById('modalDonoStars').innerHTML = !isNaN(avgNotaDono) ? renderStarsHtml(avgNotaDono) + ' <small>' + avgNotaDono.toFixed(1) + '</small>' : '';
                    var donoId = btn.dataset.donoId;
                    donoCard.href = donoId ? 'utilizador.php?id=' + donoId : '#';
                    donoCard.style.display = '';
                } else {
                    donoCard.style.display = 'none';
                }
            }

            // Fav button
            if (modalFavBtn) {
                modalFavBtn.dataset.id = btn.dataset.id;
                setModalFavState(modalFavBtn, btn.dataset.favorito === '1');
            }

            modalOverlay.classList.add('active');
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

    // Owner card
    var donoCard = document.getElementById('modalDonoCard');
    if (donoCard) {
        if (f.dono_nome) {
            document.getElementById('modalDonoNome').textContent = f.dono_nome;
            var donoAvatar = document.getElementById('modalDonoAvatar');
            donoAvatar.style.backgroundImage = f.dono_foto ? "url('" + f.dono_foto + "')" : '';
            donoAvatar.classList.toggle('dono-avatar-empty', !f.dono_foto);
            var avgNotaDono = parseFloat(f.avg_nota_dono);
            document.getElementById('modalDonoStars').innerHTML = !isNaN(avgNotaDono) ? renderStarsHtml(avgNotaDono) + ' <small>' + avgNotaDono.toFixed(1) + '</small>' : '';
            donoCard.href = f.fer_utl_id ? 'utilizador.php?id=' + f.fer_utl_id : '#';
            donoCard.style.display = '';
        } else {
            donoCard.style.display = 'none';
        }
    }

    // Fav button
    var modalFavBtn = document.getElementById('modalFavBtn');
    if (modalFavBtn) {
        modalFavBtn.dataset.id = f.fer_id;
        setModalFavState(modalFavBtn, f.is_favorito == 1);
    }

    document.getElementById('modalOverlay').classList.add('active');
};

// Home page map — reads tool data set by index.php via window.ferramentasGeo
if (typeof L !== 'undefined' && window.ferramentasGeo && document.querySelector('.map-section #mapa')) {
    var homeMap = L.map('mapa', { scrollWheelZoom: false }).setView([38.72, -9.14], 10);
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

    var formMap = L.map('mapa', { scrollWheelZoom: false }).setView([39.5, -8.0], 7);
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

// Favourites — heart button on tool cards and in the Ver mais modal
function toggleFavorito(ferId, onSuccess) {
    var fd = new FormData();
    fd.append('fer_id', ferId);
    fetch('favorito.php', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(onSuccess)
        .catch(function () {});
}

function setModalFavState(btn, isFav) {
    btn.classList.toggle('ativo', isFav);
    btn.textContent = isFav ? '♥ Favorito' : '♡ Favorito';
    btn.dataset.favorito = isFav ? '1' : '0';
}

var modalFavBtn = document.getElementById('modalFavBtn');
if (modalFavBtn) {
    modalFavBtn.addEventListener('click', function () {
        toggleFavorito(modalFavBtn.dataset.id, function (data) {
            setModalFavState(modalFavBtn, data.favorito);
        });
    });
}

var alugarFavBtn = document.getElementById('alugarFavBtn');
if (alugarFavBtn) {
    alugarFavBtn.addEventListener('click', function () {
        toggleFavorito(alugarFavBtn.dataset.id, function (data) {
            setModalFavState(alugarFavBtn, data.favorito);
        });
    });
}

// Render a tool card from AJAX data (mirrors the PHP card HTML)
function renderToolCard(f) {
    var isFav = f.is_favorito == 1;
    var card = document.createElement('article');
    card.className = 'tool-card';
    if (f.fer_lat) card.dataset.lat = f.fer_lat;
    if (f.fer_lng) card.dataset.lng = f.fer_lng;

    if (f.img_principal) {
        var img = document.createElement('img');
        img.src = f.img_principal;
        img.className = 'tool-card-img';
        img.alt = f.fer_nome;
        card.appendChild(img);
    } else {
        var ph = document.createElement('div');
        ph.className = 'tool-card-img-placeholder';
        card.appendChild(ph);
    }

    var h3 = document.createElement('h3');
    h3.textContent = f.fer_nome;
    card.appendChild(h3);

    var pCat = document.createElement('p');
    pCat.textContent = 'Categoria: ' + f.cat_nome;
    card.appendChild(pCat);

    var pPreco = document.createElement('p');
    pPreco.textContent = parseFloat(f.fer_preco_base).toFixed(2) + '€/dia';
    card.appendChild(pPreco);

    if (f.ocupada == 1) {
        var badge = document.createElement('span');
        badge.className = 'badge-indisponivel';
        badge.textContent = 'Indisponível';
        card.appendChild(badge);
    }

    var verBtn = document.createElement('button');
    verBtn.type = 'button';
    verBtn.className = 'btn-ver-mais';
    verBtn.textContent = 'Ver mais';
    verBtn.dataset.id            = f.fer_id;
    verBtn.dataset.ocupada       = f.ocupada ? '1' : '0';
    verBtn.dataset.nome          = f.fer_nome;
    verBtn.dataset.categoria     = f.cat_nome;
    verBtn.dataset.descricao     = f.fer_descricao || '';
    verBtn.dataset.preco         = parseFloat(f.fer_preco).toFixed(2);
    verBtn.dataset.precoBase     = parseFloat(f.fer_preco_base).toFixed(2);
    verBtn.dataset.descontoDias  = f.fer_desconto_dias || '';
    verBtn.dataset.precoDesconto = f.fer_preco_desconto || '';
    verBtn.dataset.avgNota       = f.avg_nota_fer || '';
    verBtn.dataset.totalAvaliacoes = f.total_avaliacoes || 0;
    verBtn.dataset.donoNome      = f.dono_nome || '';
    verBtn.dataset.donoFoto      = f.dono_foto || '';
    verBtn.dataset.avgNotaDono   = f.avg_nota_dono || '';
    verBtn.dataset.donoId        = f.fer_utl_id || '';
    verBtn.dataset.favorito      = isFav ? '1' : '0';
    verBtn.dataset.imagens       = JSON.stringify(f.img_principal ? [f.img_principal] : []);
    card.appendChild(verBtn);

    return card;
}

// Filter bar — AJAX update of the "Todas as Ferramentas" grid
var filterForm = document.querySelector('.filter-bar:not(#adminFilterForm)');
if (filterForm) {
    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var params = new URLSearchParams(new FormData(filterForm));
        params.forEach(function (v, k) { if (!v) params.delete(k); });

        var grid = document.getElementById('todasGrid');
        if (!grid) return;
        grid.style.opacity = '0.4';
        grid.style.pointerEvents = 'none';

        fetch('ferramentas_ajax.php?' + params.toString())
            .then(function (r) { return r.json(); })
            .then(function (data) {
                grid.innerHTML = '';
                grid.style.opacity = '';
                grid.style.pointerEvents = '';
                if (data.tools.length === 0) {
                    var p = document.createElement('p');
                    p.className = 'empty-msg';
                    p.textContent = 'Nenhuma ferramenta encontrada.';
                    grid.appendChild(p);
                } else {
                    data.tools.forEach(function (f) { grid.appendChild(renderToolCard(f)); });
                }
                history.pushState({}, '', 'index.php?' + params.toString());
            })
            .catch(function () {
                grid.style.opacity = '';
                grid.style.pointerEvents = '';
            });
    });
}

// Two-click delete confirmation — callable on initial load and after AJAX re-render
function setupDeleteConfirm(scope) {
    (scope || document).querySelectorAll('.delete-tool-form').forEach(function (form) {
        if (form._deleteSetup) return;
        form._deleteSetup = true;
        var btn = form.querySelector('.btn-delete-tool');
        var originalText = btn.textContent.trim();
        var confirmed = false;
        form.addEventListener('submit', function (e) {
            if (!confirmed) {
                e.preventDefault();
                confirmed = true;
                btn.textContent = 'Tens a certeza?';
                btn.style.background = '#7b1a1a';
                setTimeout(function () {
                    confirmed = false;
                    btn.textContent = originalText;
                    btn.style.background = '#c0392b';
                }, 3000);
            }
        });
    });
}
setupDeleteConfirm();

// Admin page — build a table row from JSON tool data
function renderAdminToolRow(t) {
    var ferId = t.fer_id;
    var ativa = t.fer_ativa == 1;
    var tr = document.createElement('tr');

    function td(text) {
        var el = document.createElement('td');
        el.textContent = text;
        return el;
    }

    tr.appendChild(td(ferId));
    var tdNome = document.createElement('td');
    var strong = document.createElement('strong');
    strong.textContent = t.fer_nome;
    tdNome.appendChild(strong);
    tr.appendChild(tdNome);
    tr.appendChild(td(t.cat_nome));
    tr.appendChild(td(t.dono_nome));
    tr.appendChild(td(parseFloat(t.fer_preco).toFixed(2) + '€/dia'));
    tr.appendChild(td(t.alugueres_ativos));

    var tdEstado = document.createElement('td');
    var badge = document.createElement('span');
    badge.className = 'estado-badge ' + (ativa ? 'estado-Alugado' : 'estado-Devolvido');
    badge.textContent = ativa ? 'Ativa' : 'Inativa';
    tdEstado.appendChild(badge);
    tr.appendChild(tdEstado);

    var tdAcoes = document.createElement('td');
    tdAcoes.style.cssText = 'display:flex;gap:8px;flex-wrap:wrap;';

    var formToggle = document.createElement('form');
    formToggle.method = 'post';
    formToggle.style.margin = '0';
    formToggle.innerHTML = '<input type="hidden" name="action" value="toggle_tool">' +
        '<input type="hidden" name="target_id" value="' + ferId + '">';
    var btnToggle = document.createElement('button');
    btnToggle.type = 'submit';
    btnToggle.className = 'simple-button';
    btnToggle.style.cssText = 'font-size:0.78rem;padding:5px 10px;';
    btnToggle.textContent = ativa ? 'Desativar' : 'Ativar';
    formToggle.appendChild(btnToggle);
    tdAcoes.appendChild(formToggle);

    var formDel = document.createElement('form');
    formDel.method = 'post';
    formDel.style.margin = '0';
    formDel.className = 'delete-tool-form';
    formDel.innerHTML = '<input type="hidden" name="action" value="delete_tool_admin">' +
        '<input type="hidden" name="target_id" value="' + ferId + '">';
    var btnDel = document.createElement('button');
    btnDel.type = 'submit';
    btnDel.className = 'simple-button btn-delete-tool';
    btnDel.style.cssText = 'background:#c0392b;font-size:0.78rem;padding:5px 10px;';
    btnDel.textContent = 'Eliminar';
    formDel.appendChild(btnDel);
    tdAcoes.appendChild(formDel);

    tr.appendChild(tdAcoes);
    return tr;
}

// Admin page — AJAX filter for the tools table
var adminFilterForm = document.getElementById('adminFilterForm');
if (adminFilterForm) {
    adminFilterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var params = new URLSearchParams(new FormData(adminFilterForm));
        Array.from(params.keys()).forEach(function (k) { if (!params.get(k)) params.delete(k); });

        var tbody = document.getElementById('adminToolsBody');
        tbody.style.opacity = '0.4';
        tbody.style.pointerEvents = 'none';

        fetch('admin_ajax.php?' + params.toString())
            .then(function (r) { return r.json(); })
            .then(function (data) {
                tbody.style.opacity = '';
                tbody.style.pointerEvents = '';
                tbody.innerHTML = '';
                if (data.tools.length === 0) {
                    var tr = document.createElement('tr');
                    var td = document.createElement('td');
                    td.colSpan = 8;
                    td.style.cssText = 'text-align:center;color:#aaa;';
                    td.textContent = 'Nenhuma ferramenta encontrada.';
                    tr.appendChild(td);
                    tbody.appendChild(tr);
                } else {
                    data.tools.forEach(function (t) { tbody.appendChild(renderAdminToolRow(t)); });
                    setupDeleteConfirm(tbody);
                }
                var clearLink = document.getElementById('adminClearLink');
                if (clearLink) clearLink.style.display = (params.get('cat') || params.get('nome')) ? '' : 'none';
                history.pushState({}, '', 'admin.php?' + params.toString());
            })
            .catch(function () {
                tbody.style.opacity = '';
                tbody.style.pointerEvents = '';
            });
    });
}

// Report modal — open from tool detail modal
var reportOverlay = document.getElementById('reportOverlay');
if (reportOverlay) {
    var reportClose  = document.getElementById('reportClose');
    var reportSubmit = document.getElementById('reportSubmitBtn');
    var reportMsg    = document.getElementById('reportMsg');

    var modalReportBtn = document.getElementById('modalReportBtn');
    if (modalReportBtn) {
        modalReportBtn.addEventListener('click', function () {
            var favBtn = document.getElementById('modalFavBtn');
            document.getElementById('reportFerId').value = favBtn ? favBtn.dataset.id : '';
            document.getElementById('reportMotivo').value = '';
            document.getElementById('reportDescricao').value = '';
            reportMsg.textContent = '';
            reportSubmit.style.display = '';
            reportSubmit.disabled = false;
            reportOverlay.classList.add('active');
        });
    }

    reportClose.addEventListener('click', function () { reportOverlay.classList.remove('active'); });
    reportOverlay.addEventListener('click', function (e) {
        if (e.target === reportOverlay) reportOverlay.classList.remove('active');
    });

    reportSubmit.addEventListener('click', function () {
        var ferId     = document.getElementById('reportFerId').value;
        var motivo    = document.getElementById('reportMotivo').value;
        var descricao = document.getElementById('reportDescricao').value;

        if (!motivo) {
            reportMsg.style.color = '#c0392b';
            reportMsg.textContent = 'Seleciona um motivo.';
            return;
        }

        reportSubmit.disabled = true;
        var fd = new FormData();
        fd.append('fer_id',    ferId);
        fd.append('motivo',    motivo);
        fd.append('descricao', descricao);

        fetch('reportar_ajax.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                reportSubmit.disabled = false;
                if (data.success) {
                    reportMsg.style.color = '#27ae60';
                    reportMsg.textContent = 'Denúncia enviada com sucesso.';
                    reportSubmit.style.display = 'none';
                } else if (data.error === 'already_reported') {
                    reportMsg.style.color = '#c0392b';
                    reportMsg.textContent = 'Já reportaste esta ferramenta.';
                } else {
                    reportMsg.style.color = '#c0392b';
                    reportMsg.textContent = 'Erro ao enviar. Tenta novamente.';
                }
            })
            .catch(function () {
                reportSubmit.disabled = false;
                reportMsg.style.color = '#c0392b';
                reportMsg.textContent = 'Erro de ligação.';
            });
    });
}

// Admin reports table — AJAX load and filter
var reportFilterForm = document.getElementById('reportFilterForm');
if (reportFilterForm) {
    function renderReportRow(r) {
        var tr = document.createElement('tr');
        var data = new Date(r.den_criada).toLocaleDateString('pt-PT');
        tr.innerHTML =
            '<td>' + r.den_id + '</td>' +
            '<td><a href="alugarferramenta.php?id=' + r.fer_id + '" style="color:inherit;">' + r.fer_nome + '</a></td>' +
            '<td><a href="utilizador.php?id=' + r.reporter_id + '" style="color:inherit;">' + r.reporter + '</a></td>' +
            '<td><a href="utilizador.php?id=' + r.dono_id + '" style="color:inherit;">' + r.dono_nome + '</a></td>' +
            '<td>' + r.den_motivo + '</td>' +
            '<td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + (r.den_descricao || '') + '">' + (r.den_descricao || '—') + '</td>' +
            '<td>' + data + '</td>' +
            '<td><span class="estado-badge estado-' + r.den_estado + '">' + r.den_estado + '</span></td>' +
            '<td style="display:flex;gap:6px;flex-wrap:wrap;">' +
                (r.den_estado !== 'Resolvido' ? '<button class="simple-button btn-report-action" style="font-size:0.78rem;padding:5px 10px;" data-id="' + r.den_id + '" data-estado="Resolvido">Resolvido</button>' : '') +
                (r.den_estado !== 'Ignorado'  ? '<button class="simple-button btn-report-action" style="font-size:0.78rem;padding:5px 10px;background:#888;" data-id="' + r.den_id + '" data-estado="Ignorado">Ignorar</button>'   : '') +
            '</td>';
        return tr;
    }

    function loadReports(estado) {
        var tbody = document.getElementById('reportsTbody');
        tbody.style.opacity = '0.4';
        var url = 'reportar_ajax.php' + (estado ? '?estado=' + encodeURIComponent(estado) : '');
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                tbody.style.opacity = '';
                tbody.innerHTML = '';
                if (!data.reports || data.reports.length === 0) {
                    var tr = document.createElement('tr');
                    var td = document.createElement('td');
                    td.colSpan = 8;
                    td.style.cssText = 'text-align:center;color:#aaa;';
                    td.textContent = 'Nenhuma denúncia encontrada.';
                    tr.appendChild(td);
                    tbody.appendChild(tr);
                } else {
                    data.reports.forEach(function (r) { tbody.appendChild(renderReportRow(r)); });
                }
            })
            .catch(function () { tbody.style.opacity = ''; });
    }

    // Update report state on button click
    document.getElementById('reportsTbody').addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-report-action');
        if (!btn) return;
        var fd = new FormData();
        fd.append('action', 'update_estado');
        fd.append('den_id', btn.dataset.id);
        fd.append('estado', btn.dataset.estado);
        fetch('reportar_ajax.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function () {
                loadReports(document.getElementById('reportEstadoFiltro').value);
            });
    });

    reportFilterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        loadReports(document.getElementById('reportEstadoFiltro').value);
    });

    // Load reports on page open
    loadReports('');
}