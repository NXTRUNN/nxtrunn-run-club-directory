(function($) {
    'use strict';

    let currentFilters = {
        search: '',
        state: null,
        pace: null,
        vibe: null,
        days: null,
        woman_owned: false,
        bipoc_owned: false,
        nearMe: false,
        userLat: null,
        userLng: null
    };

    let currentPage = 1;
    let totalPages = 1;
    let isLoadingMore = false;
    var CLUBS_PER_PAGE = parseInt($('.nxtrunn-directory-wrapper-new').data('per-page')) || 24;

    // Initialize directory
    function initDirectory() {
        $('.nxtrunn-filter-pill[data-filter="all"]').addClass('active').attr('aria-selected', 'true');
        loadClubs();
        bindEvents();
    }

    // Bind all events
    function bindEvents() {
        // Search
        $('#nxtrunn-search-new').on('input', debounce(function() {
            currentFilters.search = $(this).val();
            $('.nxtrunn-clear-search').toggle($(this).val().length > 0);
            loadClubs();
        }, 300));

        $('.nxtrunn-clear-search').on('click', function() {
            $('#nxtrunn-search-new').val('');
            currentFilters.search = '';
            $(this).hide();
            loadClubs();
        });

        // Filter pills - All Clubs
        $('.nxtrunn-filter-pill[data-filter="all"]').on('click', function() {
            clearAllFilters();
        });

        // Badge filters
        $('.nxtrunn-filter-pill[data-filter="badge"]').on('click', function() {
            const badge = $(this).data('badge');
            const allPill = $('.nxtrunn-filter-pill[data-filter="all"]');

            currentFilters[badge] = !currentFilters[badge];
            $(this).toggleClass('active');
            $(this).attr('aria-selected', currentFilters[badge] ? 'true' : 'false');

            // Remove All active if a badge is active
            if (currentFilters.woman_owned || currentFilters.bipoc_owned) {
                allPill.removeClass('active').attr('aria-selected', 'false');
            } else {
                allPill.addClass('active').attr('aria-selected', 'true');
            }

            loadClubs();
        });

        // Near Me
        $('#nxtrunn-near-me-new').on('click', function() {
            const $pill = $(this);

            if (currentFilters.nearMe) {
                // Toggle off
                currentFilters.nearMe = false;
                currentFilters.userLat = null;
                currentFilters.userLng = null;
                $pill.removeClass('active').attr('aria-selected', 'false');
                $pill.html('<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M12 2v4m0 12v4m10-10h-4M6 12H2"/></svg> Near Me');
                loadClubs();
                return;
            }

            if (navigator.geolocation) {
                $pill.addClass('active').text('Locating...');
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        currentFilters.nearMe = true;
                        currentFilters.userLat = position.coords.latitude;
                        currentFilters.userLng = position.coords.longitude;
                        $pill.attr('aria-selected', 'true');
                        $pill.html('<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M12 2v4m0 12v4m10-10h-4M6 12H2"/></svg> Near Me');

                        // Remove All active
                        $('.nxtrunn-filter-pill[data-filter="all"]').removeClass('active').attr('aria-selected', 'false');

                        loadNearbyClubs();
                    },
                    function() {
                        $pill.removeClass('active').attr('aria-selected', 'false');
                        $pill.html('<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M12 2v4m0 12v4m10-10h-4M6 12H2"/></svg> Near Me');
                        // Show inline message instead of alert
                        $('.nxtrunn-results-count-new .count').text('Enable location services to use Near Me');
                    }
                );
            }
        });

        // Dropdown triggers
        $('.nxtrunn-dropdown-trigger').on('click', function(e) {
            e.stopPropagation();
            const menu = $(this).siblings('.nxtrunn-dropdown-menu');
            $('.nxtrunn-dropdown-menu').not(menu).removeClass('active');
            menu.toggleClass('active');
        });

        $(document).on('click', function() {
            $('.nxtrunn-dropdown-menu').removeClass('active');
        });

        // Dropdown items
        $('.nxtrunn-dropdown-item').on('click', function(e) {
            e.stopPropagation();
            const filterType = $(this).data('filter');
            const value = $(this).data('value');

            if (currentFilters[filterType] === value) {
                currentFilters[filterType] = null;
                $(this).closest('.nxtrunn-filter-dropdown').find('.nxtrunn-dropdown-label').text(capitalizeFirst(filterType));
            } else {
                currentFilters[filterType] = value;
                const displayText = $(this).text();
                $(this).closest('.nxtrunn-filter-dropdown').find('.nxtrunn-dropdown-label').text(displayText.length > 15 ? displayText.substring(0, 15) + '...' : displayText);
            }

            $('.nxtrunn-dropdown-menu').removeClass('active');
            loadClubs();
        });

        // Clear all filters
        $('.nxtrunn-clear-all-btn').on('click', function() {
            clearAllFilters();
        });

        // Load More
        $(document).on('click', '.nxtrunn-load-more-btn', function() {
            loadMoreClubs();
        });

        // Add club modal
        $('#nxtrunn-add-club-btn').on('click', function() {
            openModal('#nxtrunn-add-club-modal');
        });

        $('#nxtrunn-close-add-modal, #nxtrunn-cancel-add-modal').on('click', function() {
            closeModal('#nxtrunn-add-club-modal');
        });

        // Close modals on backdrop click
        $(document).on('click', '.nxtrunn-modal-backdrop', function(e) {
            if ($(e.target).hasClass('nxtrunn-modal-backdrop')) {
                closeModal('#' + this.id);
            }
        });

        // Close on Escape
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.nxtrunn-modal-backdrop:visible').each(function() {
                    closeModal('#' + this.id);
                });
            }
        });

        // Prevent modal content scroll bubbling
        $(document).on('touchmove scroll', '.nxtrunn-modal-content', function(e) {
            e.stopPropagation();
        });
    }

    // Modal open/close helpers
    function openModal(selector) {
        $(selector).css('display', 'flex').hide().fadeIn(200);
        $('body').css('overflow', 'hidden');
    }

    function closeModal(selector) {
        $(selector).fadeOut(200);
        $('body').css('overflow', '');
    }

    // Load clubs via AJAX (resets to page 1)
    function loadClubs() {
        currentPage = 1;
        showLoading();

        $.ajax({
            url: nxtrunn_ajax.ajax_url,
            type: 'POST',
            timeout: 15000,
            data: buildFilterData(1),
            success: function(response) {
                hideLoading();
                if (response.success) {
                    renderClubs(response.data.clubs);
                    totalPages = response.data.pages || 1;
                    updateResultsCount(response.data.total);
                    updateLoadMoreButton();
                } else {
                    showError();
                }
            },
            error: function(xhr, status) {
                hideLoading();
                if (status === 'timeout') {
                    showError('Request timed out. Please try again.');
                } else {
                    showError();
                }
            }
        });
    }

    // Load next page of clubs (append)
    function loadMoreClubs() {
        if (isLoadingMore || currentPage >= totalPages) return;
        isLoadingMore = true;
        currentPage++;

        var $btn = $('.nxtrunn-load-more-btn');
        $btn.prop('disabled', true).text('Loading...');

        $.ajax({
            url: nxtrunn_ajax.ajax_url,
            type: 'POST',
            data: buildFilterData(currentPage),
            success: function(response) {
                isLoadingMore = false;
                if (response.success && response.data.clubs.length > 0) {
                    appendClubs(response.data.clubs);
                }
                updateLoadMoreButton();
                $btn.prop('disabled', false).text('Load More');
            },
            error: function() {
                isLoadingMore = false;
                currentPage--;
                $btn.prop('disabled', false).text('Load More');
            }
        });
    }

    // Build filter params for AJAX
    function buildFilterData(page) {
        var data = {
            action: 'nxtrunn_filter_directory',
            nonce: nxtrunn_ajax.nonce,
            search: currentFilters.search || '',
            state: currentFilters.state || null,
            pace: currentFilters.pace || null,
            vibe: currentFilters.vibe || null,
            days: currentFilters.days || null,
            woman_run: currentFilters.woman_owned ? 1 : 0,
            bipoc_owned: currentFilters.bipoc_owned ? 1 : 0,
            per_page: CLUBS_PER_PAGE,
            page: page
        };

        // Pace filter params (from My Pace modal)
        if (currentFilters.pace_min) data.pace_min = currentFilters.pace_min;
        if (currentFilters.pace_max) data.pace_max = currentFilters.pace_max;
        if (currentFilters.walker_only) data.walker_only = currentFilters.walker_only;

        return data;
    }

    // Show/hide Load More button
    function updateLoadMoreButton() {
        var $btn = $('.nxtrunn-load-more-btn');
        if (currentPage < totalPages) {
            $btn.show();
        } else {
            $btn.hide();
        }
    }

    // Load nearby clubs
    function loadNearbyClubs() {
        showLoading();

        $.ajax({
            url: nxtrunn_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'nxtrunn_search_nearby',
                nonce: nxtrunn_ajax.nonce,
                lat: currentFilters.userLat,
                lng: currentFilters.userLng,
                radius: 0,
                unit: 'mi'
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    renderClubs(response.data.clubs);
                    updateResultsCount(response.data.count);
                } else {
                    showError();
                }
            },
            error: function() {
                hideLoading();
                showError();
            }
        });
    }

    // Render clubs (replaces grid)
    function renderClubs(clubs) {
        var $grid = $('.nxtrunn-directory-grid-new');
        var $empty = $('.nxtrunn-empty-state');

        if (!clubs || clubs.length === 0) {
            $grid.empty();
            // Set contextual empty message
            var context = '';
            if (currentFilters.search) context = 'No clubs match "' + currentFilters.search + '".';
            else if (paceActive) context = 'No clubs found in that pace range.';
            else if (currentFilters.woman_owned) context = 'No Woman-Owned clubs match your filters.';
            else if (currentFilters.bipoc_owned) context = 'No BIPOC-Owned clubs match your filters.';
            else context = 'We couldn\'t find any clubs matching your filters.';
            $empty.find('.nxtrunn-empty-message').text(context);
            $empty.show();
            return;
        }

        $empty.hide();
        $grid.empty();

        clubs.forEach(function(club) {
            var card = createClubCard(club);
            $grid.append(card);
        });
    }

    // Append clubs (for Load More)
    function appendClubs(clubs) {
        var $grid = $('.nxtrunn-directory-grid-new');
        clubs.forEach(function(club) {
            var card = createClubCard(club);
            $grid.append(card);
        });
    }

    // Create club card — NEW row layout
    function createClubCard(club) {
        var safeTitle = escapeHtml(club.title);
        var initials = getInitials(club.title);
        var photoHtml = club.thumbnail ?
            '<img src="' + escapeAttr(club.thumbnail) + '" alt="' + escapeAttr(club.title) + '" loading="lazy">' :
            '<div class="nxtrunn-club-initials">' + escapeHtml(initials) + '</div>';

        var badges = [];
        if (club.badges.woman_run) {
            badges.push('<span class="nxtrunn-badge nxtrunn-badge-woman">Woman-Owned</span>');
        }
        if (club.badges.bipoc_owned) {
            badges.push('<span class="nxtrunn-badge nxtrunn-badge-bipoc">BIPOC-Owned</span>');
        }

        var sponsor = club.meta && club.meta.sponsor ? club.meta.sponsor : null;
        if (sponsor) {
            badges.push('<span class="nxtrunn-badge nxtrunn-badge-sponsor">' + escapeHtml(sponsor) + '</span>');
        }

        var pace = club.meta && club.meta.pace && club.meta.pace.length > 0 ? escapeHtml(club.meta.pace[0]) : '';
        var distanceHtml = club.distance ? '<span class="nxtrunn-club-distance">' + escapeHtml(club.distance) + '</span>' : '';
        var city = club.location && club.location.city ? escapeHtml(club.location.city) : 'Location';
        var state = club.location && club.location.state ? escapeHtml(club.location.state) : 'TBD';

        var verifiedBadge = (club.claim && club.claim.claimed) ?
            '<span class="nxtrunn-card-verified"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>Verified</span>' : '';

        return '<div class="nxtrunn-club-card" data-club-id="' + parseInt(club.id) + '" tabindex="0" role="button" aria-label="View ' + escapeAttr(club.title) + ' profile">' +
            '<div class="nxtrunn-club-photo">' + photoHtml + '</div>' +
            '<div class="nxtrunn-club-info">' +
                '<h3 class="nxtrunn-club-name">' + safeTitle + '</h3>' +
                '<div class="nxtrunn-club-location">' +
                    '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="7" r="2.5"/><path d="M8 1C4.7 1 2 3.5 2 6.6 2 10.7 8 15 8 15s6-4.3 6-8.4C14 3.5 11.3 1 8 1z"/></svg>' +
                    '<span>' + city + ', ' + state + '</span>' +
                    distanceHtml +
                '</div>' +
                '<div class="nxtrunn-club-badges">' + verifiedBadge + badges.join('') + '</div>' +
            '</div>' +
            '<div class="nxtrunn-club-meta-right">' +
                (pace ? '<span class="nxtrunn-club-pace">' + pace + '</span>' : '') +
                '<svg class="nxtrunn-club-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>' +
            '</div>' +
        '</div>';
    }

    // Open club modal
    $(document).on('click', '.nxtrunn-club-card', function() {
        var clubId = $(this).data('club-id');
        openClubModal(clubId);
    });

    // Keyboard: Enter/Space opens card
    $(document).on('keydown', '.nxtrunn-club-card', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).click();
        }
    });

    // Open club modal and load details
    function openClubModal(clubId) {
        var $modal = $('#nxtrunn-club-modal');
        var $body = $modal.find('.nxtrunn-modal-body');

        $body.html('<div class="nxtrunn-loading-new"><div class="nxtrunn-spinner"></div></div>');
        openModal('#nxtrunn-club-modal');

        $.ajax({
            url: nxtrunn_ajax.ajax_url,
            type: 'POST',
            timeout: 10000,
            data: {
                action: 'nxtrunn_get_club_details',
                nonce: nxtrunn_ajax.nonce,
                club_id: clubId
            },
            success: function(response) {
                if (response.success) {
                    renderClubModal(response.data);
                } else {
                    $body.html('<div class="nxtrunn-modal-error"><p>Unable to load club details.</p><button class="nxtrunn-cancel-btn" onclick="$(\'#nxtrunn-club-modal\').fadeOut(200);$(\'body\').css(\'overflow\',\'\');">Close</button></div>');
                }
            },
            error: function() {
                $body.html('<div class="nxtrunn-modal-error"><p>Unable to load club details. Check your connection and try again.</p><button class="nxtrunn-cancel-btn" onclick="$(\'#nxtrunn-club-modal\').fadeOut(200);$(\'body\').css(\'overflow\',\'\');">Close</button></div>');
            }
        });
    }

    // Render club modal — NEW layout
    function renderClubModal(club) {
        var safeTitle = escapeHtml(club.title);
        var initials = getInitials(club.title);
        var photoHtml = club.thumbnail ?
            '<img src="' + escapeAttr(club.thumbnail) + '" alt="' + escapeAttr(club.title) + '">' :
            '<div class="nxtrunn-modal-photo-initials">' + escapeHtml(initials) + '</div>';

        var badges = [];
        if (club.badges.woman_run) {
            badges.push('<span class="nxtrunn-badge nxtrunn-badge-woman">Woman-Owned</span>');
        }
        if (club.badges.bipoc_owned) {
            badges.push('<span class="nxtrunn-badge nxtrunn-badge-bipoc">BIPOC-Owned</span>');
        }

        var meta = club.meta || {};
        var contact = club.contact || {};
        var location = club.location || {};

        var sponsor = meta.sponsor || '';
        if (sponsor) {
            badges.push('<span class="nxtrunn-badge nxtrunn-badge-sponsor">' + escapeHtml(sponsor) + '</span>');
        }

        var description = club.content || club.excerpt || '';
        var meetingLocation = meta.meeting_location || '';
        var pace = meta.pace && meta.pace.length > 0 ? meta.pace : [];
        var vibe = meta.vibe && meta.vibe.length > 0 ? meta.vibe : [];
        var days = meta.days && meta.days.length > 0 ? meta.days : [];
        var website = contact.website || '';
        var instagram = contact.instagram || '';
        var tiktok = contact.tiktok || '';
        var strava = contact.strava || '';
        var city = escapeHtml(location.city || '');
        var state = escapeHtml(location.state || '');

        var html = '<div class="nxtrunn-modal-photo">' + photoHtml + '</div>';

        html += '<div class="nxtrunn-modal-header">';
        html += '<h2 id="nxtrunn-modal-title">' + safeTitle + '</h2>';
        html += '<p class="nxtrunn-modal-subtitle">Run Club</p>';
        html += '</div>';

        if (badges.length > 0) {
            html += '<div class="nxtrunn-modal-badges">' + badges.join('') + '</div>';
        }

        // Quick info bar
        html += '<div class="nxtrunn-modal-quick-info">';
        html += '<div class="nxtrunn-modal-info-item">';
        html += '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="7" r="2.5"/><path d="M8 1C4.7 1 2 3.5 2 6.6 2 10.7 8 15 8 15s6-4.3 6-8.4C14 3.5 11.3 1 8 1z"/></svg>';
        html += city + ', ' + state;
        html += '</div>';
        html += '</div>';

        if (description) {
            html += '<div class="nxtrunn-modal-section">';
            html += '<div class="nxtrunn-modal-section-label">About</div>';
            html += '<p>' + description + '</p>';
            html += '</div>';
            html += '<div class="nxtrunn-modal-divider"></div>';
        }

        if (meetingLocation) {
            html += '<div class="nxtrunn-modal-section">';
            html += '<div class="nxtrunn-modal-section-label">Meeting Location</div>';
            html += '<p>' + escapeHtml(meetingLocation) + '</p>';
            html += '</div>';
            html += '<div class="nxtrunn-modal-divider"></div>';
        }

        if (pace.length > 0) {
            html += '<div class="nxtrunn-modal-section">';
            html += '<div class="nxtrunn-modal-section-label">Pace</div>';
            html += '<div class="nxtrunn-modal-tags">';
            pace.forEach(function(p) {
                html += '<span class="nxtrunn-modal-tag">' + escapeHtml(p) + '</span>';
            });
            html += '</div></div>';
        }

        if (vibe.length > 0) {
            html += '<div class="nxtrunn-modal-section">';
            html += '<div class="nxtrunn-modal-section-label">Vibe</div>';
            html += '<div class="nxtrunn-modal-tags">';
            vibe.forEach(function(v) {
                html += '<span class="nxtrunn-modal-tag">' + escapeHtml(v) + '</span>';
            });
            html += '</div></div>';
        }

        if (days.length > 0) {
            html += '<div class="nxtrunn-modal-section">';
            html += '<div class="nxtrunn-modal-section-label">Run Days</div>';
            html += '<div class="nxtrunn-modal-tags">';
            days.forEach(function(d) {
                html += '<span class="nxtrunn-modal-tag">' + escapeHtml(d) + '</span>';
            });
            html += '</div></div>';
        }

        if (sponsor) {
            html += '<div class="nxtrunn-modal-divider"></div>';
            html += '<div class="nxtrunn-modal-section">';
            html += '<div class="nxtrunn-modal-section-label">Sponsored By</div>';
            html += '<div class="nxtrunn-modal-sponsor"><span>' + escapeHtml(sponsor) + '</span></div>';
            html += '</div>';
        }

        // Action buttons
        html += '<div class="nxtrunn-modal-actions">';
        if (website) {
            html += '<a href="' + escapeAttr(website) + '" class="nxtrunn-submit-link nxtrunn-external-link" target="_blank" rel="noopener">';
            html += '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px;" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>';
            html += 'Visit Website</a>';
        }
        if (instagram) {
            var igHandle = escapeAttr(instagram.replace('@', ''));
            html += '<a href="https://instagram.com/' + igHandle + '" class="nxtrunn-instagram-link nxtrunn-external-link" target="_blank" rel="noopener">';
            html += '<svg style="width:18px;height:18px;" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>';
            html += 'Instagram</a>';
        }
        if (strava) {
            html += '<a href="' + escapeAttr(strava) + '" class="nxtrunn-strava-link nxtrunn-external-link" target="_blank" rel="noopener">';
            html += '<svg style="width:18px;height:18px;" fill="currentColor" viewBox="0 0 24 24"><path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7 13.828h4.169"/></svg>';
            html += 'Strava</a>';
        }
        if (tiktok) {
            var tkHandle = escapeAttr(tiktok.replace('@', ''));
            html += '<a href="https://tiktok.com/@' + tkHandle + '" class="nxtrunn-tiktok-link nxtrunn-external-link" target="_blank" rel="noopener">';
            html += '<svg style="width:18px;height:18px;" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.48V13a8.28 8.28 0 005.58 2.16V11.7a4.84 4.84 0 01-3.77-1.24V6.69h3.77z"/></svg>';
            html += 'TikTok</a>';
        }
        html += '</div>';

        // Claim / Edit section
        html += buildClaimSection(club);

        $('#nxtrunn-club-modal .nxtrunn-modal-body').html(html);
    }

    // Build claim/edit section for modal
    function buildClaimSection(club) {
        var html = '';
        var isClaimed = club.claim && club.claim.claimed;
        var isOwner = club.claim && club.claim.is_owner;
        var isLoggedIn = parseInt(nxtrunn_ajax.is_logged_in);

        if (isOwner) {
            // Owner sees "Edit My Club" button
            html += '<div class="nxtrunn-modal-divider"></div>';
            html += '<div class="nxtrunn-claim-section">';
            html += '<div class="nxtrunn-verified-badge">';
            html += '<svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>';
            html += 'Verified Owner</div>';
            html += '<button class="nxtrunn-claim-btn nxtrunn-edit-club-btn" data-club-id="' + club.id + '" onclick="nxtrunOpenEditForm(' + club.id + ')">';
            html += '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
            html += 'Edit My Club</button>';
            html += '<div id="nxtrunn-edit-form-container"></div>';
            html += '</div>';

        } else if (isClaimed) {
            // Someone else claimed it — show verified badge only
            html += '<div class="nxtrunn-modal-divider"></div>';
            html += '<div class="nxtrunn-verified-badge" style="justify-content:center;padding:8px 0;">';
            html += '<svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>';
            html += 'Verified Club</div>';

        } else {
            // Unclaimed — show "Claim This Club"
            html += '<div class="nxtrunn-modal-divider"></div>';
            html += '<div class="nxtrunn-claim-section">';

            if (isLoggedIn) {
                html += '<button class="nxtrunn-claim-btn" onclick="nxtrunOpenClaimForm(' + club.id + ')">';
                html += '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>';
                html += 'Claim This Club</button>';
                html += '<div id="nxtrunn-claim-form-container"></div>';
            } else {
                html += '<div class="nxtrunn-claim-signup">';
                html += '<p>Is this your club? Claim it to manage your listing.</p>';
                html += '<a href="' + nxtrunn_ajax.register_url + '" onclick="document.cookie=\'nxtrunn_claim_club=' + club.id + ';path=/;max-age=3600\';">Sign Up to Claim</a>';
                html += '</div>';
            }

            html += '</div>';
        }

        return html;
    }

    // Close modal via close button
    $(document).on('click', '.nxtrunn-modal-close', function() {
        var $modal = $(this).closest('.nxtrunn-modal-backdrop');
        closeModal('#' + $modal.attr('id'));
    });

    // Update results count with proper pluralization
    function updateResultsCount(count) {
        var label = count === 1 ? '1 club found' : count + ' clubs found';
        $('.nxtrunn-results-count-new .count').text(label);
    }

    // Clear all filters
    function clearAllFilters() {
        currentFilters = {
            search: '',
            state: null,
            pace: null,
            vibe: null,
            days: null,
            woman_owned: false,
            bipoc_owned: false,
            nearMe: false,
            userLat: null,
            userLng: null
        };

        // Reset pace state
        paceActive = false;
        delete currentFilters.pace_min;
        delete currentFilters.pace_max;
        delete currentFilters.walker_only;

        $('#nxtrunn-search-new').val('');
        $('.nxtrunn-clear-search').hide();

        // Reset all pills
        $('.nxtrunn-filter-pill').removeClass('active').attr('aria-selected', 'false');
        $('.nxtrunn-filter-pill[data-filter="all"]').addClass('active').attr('aria-selected', 'true');

        // Reset pace pill text + sliders
        $('#nxtrunn-pace-pill').text('My Pace');
        $('#nxtrunn-walker-btn').removeClass('active');
        $('#nxtrunn-pace-min').val(540);
        $('#nxtrunn-pace-max').val(720);

        // Reset dropdowns
        $('.nxtrunn-dropdown-label').each(function() {
            var filterType = $(this).closest('.nxtrunn-filter-dropdown').find('.nxtrunn-dropdown-item').first().data('filter');
            $(this).text(capitalizeFirst(filterType));
        });

        // Reset Near Me
        $('#nxtrunn-near-me-new').removeClass('active').attr('aria-selected', 'false')
            .html('<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M12 2v4m0 12v4m10-10h-4M6 12H2"/></svg> Near Me');

        loadClubs();
    }

    // Helpers
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escapeAttr(str) {
        return escapeHtml(str).replace(/"/g, '&quot;');
    }

    function getInitials(name) {
        if (!name) return '';
        return name.split(' ').map(function(word) { return word[0]; }).join('').substring(0, 2).toUpperCase();
    }

    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function debounce(func, wait) {
        var timeout;
        return function() {
            var context = this;
            var args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    function showLoading() {
        $('.nxtrunn-loading-new').show();
        $('.nxtrunn-directory-grid-new').css({ opacity: '0.5', 'pointer-events': 'none' });
    }

    function hideLoading() {
        $('.nxtrunn-loading-new').hide();
        $('.nxtrunn-directory-grid-new').css({ opacity: '1', 'pointer-events': '' });
    }

    function showError(msg) {
        var message = msg || 'Unable to load clubs. Please try again.';
        $('.nxtrunn-directory-grid-new').html(
            '<div style="text-align:center;padding:48px 24px;">' +
            '<p style="color:var(--color-text-secondary);margin-bottom:16px;">' + message + '</p>' +
            '<button class="nxtrunn-clear-all-btn" onclick="location.reload()">Refresh Page</button>' +
            '</div>'
        );
    }

    // ========================================
    // PACE FILTER
    // ========================================

    var paceActive = false;
    var paceUnit = 'mi';

    function secToDisplay(sec) {
        var mins = Math.floor(sec / 60);
        var secs = sec % 60;
        return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    }

    function secToKm(sec) {
        // Convert min/mile to min/km (multiply by 0.621371)
        var kmSec = Math.round(sec * 0.621371);
        var mins = Math.floor(kmSec / 60);
        var secs = kmSec % 60;
        return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    }

    function updatePaceDisplay() {
        var minVal = parseInt($('#nxtrunn-pace-min').val());
        var maxVal = parseInt($('#nxtrunn-pace-max').val());

        // Ensure min <= max
        if (minVal > maxVal) {
            $('#nxtrunn-pace-max').val(minVal);
            maxVal = minVal;
        }

        if (paceUnit === 'mi') {
            $('#nxtrunn-pace-label').text(secToDisplay(minVal) + ' - ' + secToDisplay(maxVal));
            $('#nxtrunn-pace-unit-label').text('min/mi');
        } else {
            $('#nxtrunn-pace-label').text(secToKm(minVal) + ' - ' + secToKm(maxVal));
            $('#nxtrunn-pace-unit-label').text('min/km');
        }
    }

    // Open pace modal
    $('#nxtrunn-pace-pill').on('click', function() {
        if (paceActive) {
            // If pace is active and user clicks again, open modal to adjust
            openModal('#nxtrunn-pace-modal');
        } else {
            openModal('#nxtrunn-pace-modal');
        }
    });

    // Close pace modal
    $('#nxtrunn-pace-close').on('click', function() {
        closeModal('#nxtrunn-pace-modal');
    });

    // Unit toggle
    $(document).on('click', '.nxtrunn-unit-btn', function() {
        $('.nxtrunn-unit-btn').removeClass('active');
        $(this).addClass('active');
        paceUnit = $(this).data('unit');
        updatePaceDisplay();
    });

    // Slider changes
    $(document).on('input', '#nxtrunn-pace-min, #nxtrunn-pace-max', function() {
        updatePaceDisplay();
    });

    // Walker toggle
    $(document).on('click', '#nxtrunn-walker-btn', function() {
        var isActive = $(this).toggleClass('active').hasClass('active');
        if (isActive) {
            $('#nxtrunn-pace-min').val(1200); // 20:00
            $('#nxtrunn-pace-max').val(1800); // 30:00
            updatePaceDisplay();
        }
    });

    // Apply pace filter
    $(document).on('click', '#nxtrunn-pace-apply', function() {
        paceActive = true;
        currentFilters.pace_min = parseInt($('#nxtrunn-pace-min').val());
        currentFilters.pace_max = parseInt($('#nxtrunn-pace-max').val());
        currentFilters.walker_only = $('#nxtrunn-walker-btn').hasClass('active') ? 1 : 0;

        // Update pill appearance
        var $pill = $('#nxtrunn-pace-pill');
        var label = secToDisplay(currentFilters.pace_min) + ' - ' + secToDisplay(currentFilters.pace_max);
        $pill.addClass('active').attr('aria-selected', 'true').text(label);
        $('.nxtrunn-filter-pill[data-filter="all"]').removeClass('active').attr('aria-selected', 'false');

        closeModal('#nxtrunn-pace-modal');
        loadClubs();
    });

    // Clear pace filter
    $(document).on('click', '#nxtrunn-pace-clear', function() {
        paceActive = false;
        delete currentFilters.pace_min;
        delete currentFilters.pace_max;
        delete currentFilters.walker_only;

        $('#nxtrunn-pace-pill').removeClass('active').attr('aria-selected', 'false').text('My Pace');
        $('#nxtrunn-walker-btn').removeClass('active');
        $('#nxtrunn-pace-min').val(540);
        $('#nxtrunn-pace-max').val(720);
        updatePaceDisplay();

        closeModal('#nxtrunn-pace-modal');

        // Restore All Clubs if no other filters active
        if (!currentFilters.woman_owned && !currentFilters.bipoc_owned && !currentFilters.nearMe) {
            $('.nxtrunn-filter-pill[data-filter="all"]').addClass('active').attr('aria-selected', 'true');
        }

        loadClubs();
    });

    // ========================================
    // CLAIM CLUB FUNCTIONS
    // ========================================

    // Open claim form (global so onclick works)
    window.nxtrunOpenClaimForm = function(clubId) {
        var $container = $('#nxtrunn-claim-form-container');
        $container.html(
            '<div class="nxtrunn-claim-form">' +
                '<div class="nxtrunn-claim-form-title">Claim This Club</div>' +
                '<div class="nxtrunn-claim-form-subtitle">We\'ll send a verification code to your club\'s email to confirm ownership.</div>' +
                '<div class="nxtrunn-claim-message" id="nxtrunn-claim-msg"></div>' +
                '<div class="nxtrunn-claim-field">' +
                    '<label>Your Role <small>(required)</small></label>' +
                    '<select id="nxtrunn-claim-role">' +
                        '<option value="">Select your role...</option>' +
                        '<option value="founder">Founder</option>' +
                        '<option value="captain">Captain / Run Leader</option>' +
                        '<option value="admin">Club Admin</option>' +
                        '<option value="ambassador">Ambassador</option>' +
                    '</select>' +
                '</div>' +
                '<div class="nxtrunn-claim-field">' +
                    '<label>Club Contact Email <small>(we\'ll send a code here)</small></label>' +
                    '<input type="email" id="nxtrunn-claim-email" placeholder="yourclub@email.com">' +
                '</div>' +
                '<button class="nxtrunn-claim-submit" id="nxtrunn-claim-send" data-club-id="' + clubId + '">Send Verification Code</button>' +
            '</div>'
        );

        // Scroll to form
        $container[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    // Send claim / verification code
    $(document).on('click', '#nxtrunn-claim-send', function() {
        var $btn = $(this);
        var clubId = $btn.data('club-id');
        var role = $('#nxtrunn-claim-role').val();
        var email = $('#nxtrunn-claim-email').val();
        var $msg = $('#nxtrunn-claim-msg');

        if (!role || !email) {
            $msg.removeClass('success').addClass('error').text('Please fill in all fields.').show();
            return;
        }

        $btn.prop('disabled', true).text('Sending...');
        $msg.hide();

        $.ajax({
            url: nxtrunn_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'nxtrunn_submit_claim',
                nonce: nxtrunn_ajax.nonce,
                club_id: clubId,
                club_email: email,
                role: role
            },
            success: function(response) {
                if (response.success) {
                    // Show code entry form
                    showCodeEntry(clubId, email);
                } else {
                    $msg.removeClass('success').addClass('error').text(response.data.message).show();
                    $btn.prop('disabled', false).text('Send Verification Code');
                }
            },
            error: function() {
                $msg.removeClass('success').addClass('error').text('Something went wrong. Please try again.').show();
                $btn.prop('disabled', false).text('Send Verification Code');
            }
        });
    });

    // Show code entry UI
    function showCodeEntry(clubId, email) {
        var $container = $('#nxtrunn-claim-form-container');
        $container.html(
            '<div class="nxtrunn-claim-form">' +
                '<div class="nxtrunn-code-section">' +
                    '<div class="nxtrunn-claim-form-title">Enter Your Code</div>' +
                    '<p>We sent a 6-digit code to<br><strong>' + escapeHtml(email) + '</strong></p>' +
                    '<div class="nxtrunn-claim-message" id="nxtrunn-verify-msg"></div>' +
                    '<input type="text" class="nxtrunn-code-input" id="nxtrunn-code-input" maxlength="6" placeholder="000000" inputmode="numeric" pattern="[0-9]*">' +
                    '<br>' +
                    '<button class="nxtrunn-verify-btn" id="nxtrunn-verify-code" data-club-id="' + clubId + '">Verify</button>' +
                '</div>' +
            '</div>'
        );

        // Auto-focus code input
        $('#nxtrunn-code-input').focus();
    }

    // Verify code
    $(document).on('click', '#nxtrunn-verify-code', function() {
        var $btn = $(this);
        var clubId = $btn.data('club-id');
        var code = $('#nxtrunn-code-input').val().trim();
        var $msg = $('#nxtrunn-verify-msg');

        if (!code || code.length < 6) {
            $msg.removeClass('success').addClass('error').text('Please enter the 6-digit code.').show();
            return;
        }

        $btn.prop('disabled', true).text('Verifying...');
        $msg.hide();

        $.ajax({
            url: nxtrunn_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'nxtrunn_verify_claim',
                nonce: nxtrunn_ajax.nonce,
                club_id: clubId,
                code: code
            },
            success: function(response) {
                if (response.success) {
                    showClaimSuccess();
                } else {
                    $msg.removeClass('success').addClass('error').text(response.data.message).show();
                    $btn.prop('disabled', false).text('Verify');
                }
            },
            error: function() {
                $msg.removeClass('success').addClass('error').text('Something went wrong. Please try again.').show();
                $btn.prop('disabled', false).text('Verify');
            }
        });
    });

    // Allow Enter key to submit code
    $(document).on('keydown', '#nxtrunn-code-input', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#nxtrunn-verify-code').click();
        }
    });

    // Show claim success
    function showClaimSuccess() {
        var $container = $('#nxtrunn-claim-form-container');
        $container.html(
            '<div class="nxtrunn-claim-success">' +
                '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' +
                '<h3>Club Verified!</h3>' +
                '<p>You now own this listing. Close this modal and reopen the club to edit your details.</p>' +
            '</div>'
        );
    }

    // ========================================
    // EDIT CLUB FUNCTIONS (for verified owners)
    // ========================================

    window.nxtrunOpenEditForm = function(clubId) {
        var $container = $('#nxtrunn-edit-form-container');

        // If form is already open, close it
        if ($container.children().length > 0) {
            $container.empty();
            return;
        }

        $container.html('<div class="nxtrunn-loading-new" style="padding:24px;"><div class="nxtrunn-spinner"></div></div>');

        // Fetch current club data to pre-fill
        $.ajax({
            url: nxtrunn_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'nxtrunn_get_club_details',
                nonce: nxtrunn_ajax.nonce,
                club_id: clubId
            },
            success: function(response) {
                if (response.success) {
                    renderEditForm(clubId, response.data);
                }
            }
        });
    };

    function renderEditForm(clubId, club) {
        var $container = $('#nxtrunn-edit-form-container');
        var desc = club.content ? club.content.replace(/<[^>]*>/g, '') : '';
        var website = club.contact.website || '';
        var instagram = club.contact.instagram || '';
        var tiktok = club.contact.tiktok || '';
        var strava = club.contact.strava || '';
        var meeting = club.meta.meeting_location || '';
        var city = club.location.city || '';
        var state = club.location.state || '';

        var html = '<div class="nxtrunn-claim-form">' +
            '<div class="nxtrunn-modal-divider"></div>' +
            '<div class="nxtrunn-claim-form-title">Edit Your Club</div>' +
            '<div class="nxtrunn-claim-message" id="nxtrunn-edit-msg"></div>' +
            '<div class="nxtrunn-claim-field">' +
                '<label>Description</label>' +
                '<textarea id="nxtrunn-edit-desc" rows="3">' + escapeHtml(desc) + '</textarea>' +
            '</div>' +
            '<div class="nxtrunn-claim-field">' +
                '<label>City</label>' +
                '<input type="text" id="nxtrunn-edit-city" value="' + escapeAttr(city) + '">' +
            '</div>' +
            '<div class="nxtrunn-claim-field">' +
                '<label>State</label>' +
                '<input type="text" id="nxtrunn-edit-state" value="' + escapeAttr(state) + '">' +
            '</div>' +
            '<div class="nxtrunn-claim-field">' +
                '<label>Meeting Location</label>' +
                '<input type="text" id="nxtrunn-edit-meeting" value="' + escapeAttr(meeting) + '">' +
            '</div>' +
            '<div class="nxtrunn-claim-field">' +
                '<label>Website</label>' +
                '<input type="url" id="nxtrunn-edit-website" value="' + escapeAttr(website) + '" placeholder="https://">' +
            '</div>' +
            '<div class="nxtrunn-claim-field">' +
                '<label>Instagram Handle</label>' +
                '<input type="text" id="nxtrunn-edit-instagram" value="' + escapeAttr(instagram) + '" placeholder="@yourclub">' +
            '</div>' +
            '<div class="nxtrunn-claim-field">' +
                '<label>TikTok Handle</label>' +
                '<input type="text" id="nxtrunn-edit-tiktok" value="' + escapeAttr(tiktok) + '" placeholder="@yourclub">' +
            '</div>' +
            '<div class="nxtrunn-claim-field">' +
                '<label>Strava Club URL</label>' +
                '<input type="url" id="nxtrunn-edit-strava" value="' + escapeAttr(strava) + '" placeholder="https://strava.com/clubs/...">' +
            '</div>' +
            '<div class="nxtrunn-claim-field">' +
                '<label>Club Logo</label>' +
                '<input type="file" id="nxtrunn-edit-logo" accept="image/*">' +
            '</div>' +
            '<button class="nxtrunn-claim-submit" id="nxtrunn-save-edits" data-club-id="' + clubId + '">Save Changes</button>' +
        '</div>';

        $container.html(html);
        $container[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Save club edits
    $(document).on('click', '#nxtrunn-save-edits', function() {
        var $btn = $(this);
        var clubId = $btn.data('club-id');
        var $msg = $('#nxtrunn-edit-msg');

        $btn.prop('disabled', true).text('Saving...');
        $msg.hide();

        var formData = new FormData();
        formData.append('action', 'nxtrunn_save_club_edits');
        formData.append('nonce', nxtrunn_ajax.nonce);
        formData.append('club_id', clubId);
        formData.append('description', $('#nxtrunn-edit-desc').val());
        formData.append('city', $('#nxtrunn-edit-city').val());
        formData.append('state', $('#nxtrunn-edit-state').val());
        formData.append('meeting_location', $('#nxtrunn-edit-meeting').val());
        formData.append('website', $('#nxtrunn-edit-website').val());
        formData.append('instagram', $('#nxtrunn-edit-instagram').val());
        formData.append('tiktok', $('#nxtrunn-edit-tiktok').val());
        formData.append('strava', $('#nxtrunn-edit-strava').val());

        var logoFile = $('#nxtrunn-edit-logo')[0].files[0];
        if (logoFile) {
            formData.append('club_logo', logoFile);
        }

        $.ajax({
            url: nxtrunn_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $msg.removeClass('error').addClass('success').text(response.data.message).show();
                    $btn.prop('disabled', false).text('Save Changes');
                } else {
                    $msg.removeClass('success').addClass('error').text(response.data.message).show();
                    $btn.prop('disabled', false).text('Save Changes');
                }
            },
            error: function() {
                $msg.removeClass('success').addClass('error').text('Something went wrong. Please try again.').show();
                $btn.prop('disabled', false).text('Save Changes');
            }
        });
    });

    // Initialize
    $(document).ready(function() {
        if ($('.nxtrunn-directory-wrapper-new').length) {
            initDirectory();

            // Auto-open claim modal if ?open_claim= param or cookie exists
            var urlParams = new URLSearchParams(window.location.search);
            var claimClubId = urlParams.get('open_claim');

            // Also check cookie (MemberPress bypasses WP redirect filters)
            if (!claimClubId && parseInt(nxtrunn_ajax.is_logged_in)) {
                var cookieMatch = document.cookie.match(/nxtrunn_claim_club=(\d+)/);
                if (cookieMatch) {
                    claimClubId = cookieMatch[1];
                    // Clear the cookie
                    document.cookie = 'nxtrunn_claim_club=;path=/;max-age=0';
                }
            }

            if (claimClubId) {
                // Small delay to let directory load, then open the club modal
                setTimeout(function() {
                    openClubModal(parseInt(claimClubId));
                }, 800);

                // Clean URL
                var cleanUrl = window.location.pathname;
                window.history.replaceState({}, '', cleanUrl);
            }
        }

        // PWA-compatible external link handler
        $(document).on('click', '.nxtrunn-external-link', function(e) {
            e.preventDefault();
            var url = $(this).attr('href');

            if (window.matchMedia('(display-mode: standalone)').matches ||
                window.navigator.standalone === true) {
                window.open(url, '_blank', 'noopener,noreferrer');
            } else {
                window.location.href = url;
            }
        });
    });

})(jQuery);
