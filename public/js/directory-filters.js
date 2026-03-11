jQuery(document).ready(function($) {
    
    'use strict';
    
    const DirectoryFilters = {
        
        init: function() {
            this.bindEvents();
            this.loadInitialClubs();
            this.setupMobileFilters();
        },
        
        bindEvents: function() {
            // Apply filters button
            $('.nxtrunn-apply-filters').on('click', this.applyFilters.bind(this));
            
            // Clear filters button
            $('.nxtrunn-clear-filters').on('click', this.clearFilters.bind(this));
            
            // Search input (debounced)
            let searchTimeout;
            $('#nxtrunn-search').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    DirectoryFilters.applyFilters();
                }, 500);
            });
            
            // Sort dropdown
            $('#nxtrunn-sort-by').on('change', this.applyFilters.bind(this));
        },
        
        setupMobileFilters: function() {
            // Mobile filter toggle
            if ($(window).width() <= 1024) {
                $('.nxtrunn-filters').prepend('<button class="nxtrunn-filter-toggle">🔍 Show Filters</button>');
                
                // Wrap filter groups (except search and near me)
                $('.nxtrunn-filter-group').slice(2).wrapAll('<div class="nxtrunn-filter-content"></div>');
                
                $('.nxtrunn-filter-toggle').on('click', function() {
                    $('.nxtrunn-filter-content').slideToggle().toggleClass('active');
                    $(this).text($('.nxtrunn-filter-content').hasClass('active') ? '✖ Hide Filters' : '🔍 Show Filters');
                });
            }
        },
        
        loadInitialClubs: function() {
            this.applyFilters();
        },
        
        applyFilters: function() {
            
            const $grid = $('.nxtrunn-directory-grid');
            const $loading = $('.nxtrunn-loading');
            
            // Show loading
            $loading.show();
            $grid.css('opacity', '0.5');
            
            // Gather filter data
            const filterData = {
                action: 'nxtrunn_filter_directory',
                nonce: nxtrunn_ajax.nonce,
                search: $('#nxtrunn-search').val(),
                country: $('#nxtrunn-filter-country').val(),
                state: $('#nxtrunn-filter-state').val(),
                city: $('#nxtrunn-filter-city').val(),
                woman_run: $('input[name="woman_run"]').is(':checked') ? 1 : '',
                bipoc_owned: $('input[name="bipoc_owned"]').is(':checked') ? 1 : '',
                pace: [],
                vibe: [],
                days: [],
                per_page: $('.nxtrunn-directory-wrapper').data('per-page') || 12,
                page: 1
            };
            
            // Collect checked taxonomies
            $('input[name="pace[]"]:checked').each(function() {
                filterData.pace.push($(this).val());
            });
            
            $('input[name="vibe[]"]:checked').each(function() {
                filterData.vibe.push($(this).val());
            });
            
            $('input[name="days[]"]:checked').each(function() {
                filterData.days.push($(this).val());
            });
            
            // AJAX request
            $.ajax({
                url: nxtrunn_ajax.ajax_url,
                type: 'POST',
                data: filterData,
                success: function(response) {
                    
                    if (response.success) {
                        DirectoryFilters.renderClubs(response.data.clubs);
                        DirectoryFilters.updateResultCount(response.data.total);
                        DirectoryFilters.renderPagination(response.data.pages, 1);
                    } else {
                        console.error('Filter error:', response.data.message);
                    }
                    
                    $loading.hide();
                    $grid.css('opacity', '1');
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    $loading.hide();
                    $grid.css('opacity', '1');
                }
            });
        },
        
        renderClubs: function(clubs) {
            
            const $grid = $('.nxtrunn-directory-grid');
            $grid.empty();
            
            if (clubs.length === 0) {
                $grid.html('<p style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #999;">No clubs found. Try adjusting your filters.</p>');
                return;
            }
            
            clubs.forEach(function(club) {
                const card = DirectoryFilters.buildClubCard(club);
                $grid.append(card);
            });
        },
        
        buildClubCard: function(club) {
            
            let thumbnail = '';
            if (club.thumbnail) {
                thumbnail = `<div class="nxtrunn-club-thumbnail"><img src="${club.thumbnail}" alt="${club.title}"></div>`;
            }
            
            let distance = '';
            if (club.distance) {
                distance = `<span class="nxtrunn-distance">${club.distance} away</span>`;
            }
            
            let pace = '';
            if (club.meta.pace && club.meta.pace.length > 0) {
                pace = `<span class="nxtrunn-meta-item">🏃🏾 ${club.meta.pace.join(', ')}</span>`;
            }
            
            let vibe = '';
            if (club.meta.vibe && club.meta.vibe.length > 0) {
                vibe = `<span class="nxtrunn-meta-item">✨ ${club.meta.vibe.join(', ')}</span>`;
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
                            ${distance}
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
        
        updateResultCount: function(total) {
            const text = total === 1 ? '1 club found' : `${total} clubs found`;
            $('.nxtrunn-results-count .count').text(text);
        },
        
        renderPagination: function(totalPages, currentPage) {
            
            const $pagination = $('.nxtrunn-pagination');
            $pagination.empty();
            
            if (totalPages <= 1) {
                return;
            }
            
            // Previous button
            const prevDisabled = currentPage === 1 ? 'disabled' : '';
            $pagination.append(`<button ${prevDisabled} data-page="${currentPage - 1}">← Previous</button>`);
            
            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                const active = i === currentPage ? 'active' : '';
                $pagination.append(`<button class="${active}" data-page="${i}">${i}</button>`);
            }
            
            // Next button
            const nextDisabled = currentPage === totalPages ? 'disabled' : '';
            $pagination.append(`<button ${nextDisabled} data-page="${currentPage + 1}">Next →</button>`);
            
            // Bind pagination clicks
            $pagination.find('button').on('click', function() {
                if (!$(this).is(':disabled') && !$(this).hasClass('active')) {
                    DirectoryFilters.loadPage($(this).data('page'));
                }
            });
        },
        
        loadPage: function(page) {
            // Similar to applyFilters but with page parameter
            // Implementation left for brevity
        },
        
        clearFilters: function() {
            // Clear all inputs
            $('#nxtrunn-search').val('');
            $('#nxtrunn-filter-country').val('');
            $('#nxtrunn-filter-state').val('');
            $('#nxtrunn-filter-city').val('');
            $('input[type="checkbox"]').prop('checked', false);
            
            // Reload clubs
            this.applyFilters();
        }
    };
    
    // Initialize
    if ($('.nxtrunn-directory-wrapper').length) {
        DirectoryFilters.init();
    }
    
});