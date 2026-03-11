jQuery(document).ready(function($) {
    
    'use strict';
    
    const Geolocation = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            $('#nxtrunn-near-me').on('click', this.findNearbyClubs.bind(this));
        },
        
        findNearbyClubs: function(e) {
            
            e.preventDefault();
            
            const $btn = $(e.currentTarget);
            const $loading = $('.nxtrunn-loading');
            const originalText = $btn.text();
            
            // Check if geolocation is supported
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser. Please use manual search instead.');
                return;
            }
            
            // Update button state
            $btn.text('Finding your location...').prop('disabled', true);
            $loading.show();
            
            // Get user's position
            navigator.geolocation.getCurrentPosition(
                
                // Success callback
                (position) => {
                    this.searchByCoordinates(
                        position.coords.latitude,
                        position.coords.longitude,
                        $btn,
                        originalText
                    );
                },
                
                // Error callback
                (error) => {
                    
                    let message = 'Could not get your location. ';
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            message += 'Please enable location access in your browser settings.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            message += 'Location information is unavailable.';
                            break;
                        case error.TIMEOUT:
                            message += 'The request timed out. Please try again.';
                            break;
                        default:
                            message += 'Please try manual search instead.';
                    }
                    
                    alert(message);
                    
                    $btn.text(originalText).prop('disabled', false);
                    $loading.hide();
                },
                
                // Options
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000 // Cache for 5 minutes
                }
            );
        },
        
        searchByCoordinates: function(lat, lng, $btn, originalText) {
            
            const radius = $('#nxtrunn-distance-radius').val() || 25;
            const $loading = $('.nxtrunn-loading');
            const $grid = $('.nxtrunn-directory-grid');
            
            $grid.css('opacity', '0.5');
            
            $.ajax({
                url: nxtrunn_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'nxtrunn_search_nearby',
                    nonce: nxtrunn_ajax.nonce,
                    lat: lat,
                    lng: lng,
                    radius: radius,
                    unit: 'mi' // Could be dynamic based on country
                },
                success: (response) => {
                    
                    if (response.success) {
                        
                        this.renderNearbyClubs(response.data.clubs);
                        this.updateResultCount(response.data.count, true);
                        
                        // Update URL for sharing/bookmarking
                        if (history.pushState) {
                            const newUrl = window.location.pathname + `?lat=${lat}&lng=${lng}&radius=${radius}`;
                            history.pushState({path: newUrl}, '', newUrl);
                        }
                        
                    } else {
                        alert('Error finding clubs: ' + response.data.message);
                    }
                    
                    $btn.text(originalText).prop('disabled', false);
                    $loading.hide();
                    $grid.css('opacity', '1');
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', error);
                    alert('An error occurred. Please try again.');
                    
                    $btn.text(originalText).prop('disabled', false);
                    $loading.hide();
                    $grid.css('opacity', '1');
                }
            });
        },
        
        renderNearbyClubs: function(clubs) {
            
            const $grid = $('.nxtrunn-directory-grid');
            $grid.empty();
            
            if (clubs.length === 0) {
                $grid.html(`
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                        <p style="font-size: 18px; color: #999; margin-bottom: 15px;">
                            No clubs found near you.
                        </p>
                        <p style="color: #666;">
                            Try increasing the distance radius or use manual search.
                        </p>
                    </div>
                `);
                return;
            }
            
            clubs.forEach((club) => {
                const card = this.buildClubCard(club);
                $grid.append(card);
            });
        },
        
        buildClubCard: function(club) {
            
            let thumbnail = '';
            if (club.thumbnail) {
                thumbnail = `<div class="nxtrunn-club-thumbnail"><img src="${club.thumbnail}" alt="${club.title}"></div>`;
            }
            
            let pace = '';
            if (club.meta.pace && club.meta.pace.length > 0) {
                pace = `<span class="nxtrunn-meta-item">🏃 ${club.meta.pace.join(', ')}</span>`;
            }
            
            let vibe = '';
            if (club.meta.vibe && club.meta.vibe.length > 0) {
                vibe = `<span class="nxtrunn-meta-item">🌟 ${club.meta.vibe.join(', ')}</span>`;
            }
            
            let days = '';
            if (club.meta.days && club.meta.days.length > 0) {
                days = `<span class="nxtrunn-meta-item">📅 ${club.meta.days.join(', ')}</span>`;
            }
            
            return `
                <div class="nxtrunn-club-card" data-club-id="${club.id}">
                    ${thumbnail}
                    <div class="nxtrunn-club-content">
                        <div class="nxtrunn-club-badges">
                            ${club.badges_html}
                        </div>
                        <h3 class="nxtrunn-club-title">
                            <a href="${club.url}">${club.title}</a>
                        </h3>
                        <div class="nxtrunn-club-location">
                            📍 ${club.location.city}, ${club.location.state}
                            <span class="nxtrunn-distance">${club.distance} away</span>
                        </div>
                        <div class="nxtrunn-club-excerpt">
                            ${club.excerpt}
                        </div>
                        <div class="nxtrunn-club-meta">
                            ${pace}
                            ${vibe}
                            ${days}
                        </div>
                        <a href="${club.url}" class="nxtrunn-club-link">View Club Details →</a>
                    </div>
                </div>
            `;
        },
        
        updateResultCount: function(count, isNearby) {
            const text = count === 1 
                ? `1 club found ${isNearby ? 'near you' : ''}` 
                : `${count} clubs found ${isNearby ? 'near you' : ''}`;
            $('.nxtrunn-results-count .count').text(text);
        }
    };
    
    // Initialize
    if ($('.nxtrunn-directory-wrapper').length) {
        Geolocation.init();
    }
    
});