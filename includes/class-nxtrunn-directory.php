<?php
/**
 * Directory display logic — Redesigned with mobile-first layout
 */
class NXTRUNN_Directory {

    /**
     * Render the directory
     */
    public static function render( $atts ) {

        $atts = shortcode_atts( array(
            'per_page' => get_option( 'nxtrunn_clubs_per_page', 12 ),
        ), $atts );

        ob_start();

        ?>
        <div class="nxtrunn-directory-wrapper-new" data-per-page="<?php echo esc_attr( $atts['per_page'] ); ?>">

            <!-- Sticky Header with Search -->
            <div class="nxtrunn-header-new">
                <div class="nxtrunn-search-add-container">
                    <div class="nxtrunn-search-wrapper">
                        <svg class="nxtrunn-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input
                            type="text"
                            id="nxtrunn-search-new"
                            placeholder="Search clubs by name or city..."
                            class="nxtrunn-search-input-new"
                            aria-label="Search run clubs"
                        >
                        <button class="nxtrunn-clear-search" style="display: none;" aria-label="Clear search">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <button class="nxtrunn-add-club-btn" id="nxtrunn-add-club-btn" aria-label="Add your run club">
                        + Add Club
                    </button>
                </div>
            </div>

            <!-- Filter Bar — Segmented Control -->
            <div class="nxtrunn-filters-new" role="tablist" aria-label="Filter clubs">
                <div class="nxtrunn-filter-pills-container">

                    <button class="nxtrunn-filter-pill active" data-filter="all" role="tab" aria-selected="true">
                        All Clubs
                    </button>

                    <button class="nxtrunn-filter-pill" id="nxtrunn-near-me-new" role="tab" aria-selected="false">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <circle cx="12" cy="12" r="3"/>
                            <path stroke-linecap="round" d="M12 2v4m0 12v4m10-10h-4M6 12H2"/>
                        </svg>
                        Near Me
                    </button>

                    <button class="nxtrunn-filter-pill" id="nxtrunn-pace-pill" role="tab" aria-selected="false">
                        My Pace
                    </button>

                    <button class="nxtrunn-filter-pill" data-filter="badge" data-badge="woman_owned" role="tab" aria-selected="false">
                        Woman-Owned
                    </button>

                    <button class="nxtrunn-filter-pill" data-filter="badge" data-badge="bipoc_owned" role="tab" aria-selected="false">
                        BIPOC-Owned
                    </button>

                </div>
            </div>

            <!-- Results Count -->
            <div class="nxtrunn-results-count-new" aria-live="polite">
                <span class="count">Loading clubs...</span>
            </div>

            <!-- Club Cards Grid -->
            <div class="nxtrunn-directory-grid-new">
                <!-- Clubs loaded via AJAX -->
            </div>

            <!-- Load More Button -->
            <div class="nxtrunn-load-more-wrap" style="text-align: center; padding: 24px 0;">
                <button class="nxtrunn-load-more-btn" style="display: none;">Load More</button>
            </div>

            <!-- Empty State -->
            <div class="nxtrunn-empty-state" style="display: none;">
                <div class="nxtrunn-empty-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <h3>No Clubs Found</h3>
                <p>We couldn't find any clubs matching your search.</p>
                <p>Try searching by city name, or browse all clubs to explore.</p>
                <button class="nxtrunn-clear-all-btn">Show All Clubs</button>
            </div>

            <!-- Loading Spinner -->
            <div class="nxtrunn-loading-new" style="display: none;">
                <div class="nxtrunn-spinner"></div>
            </div>

        </div>

        <!-- Club Details Modal -->
        <div class="nxtrunn-modal-backdrop" id="nxtrunn-club-modal" role="dialog" aria-modal="true" aria-label="Club details" style="display: none;">
            <div class="nxtrunn-modal-content">
                <div class="nxtrunn-modal-drag-handle"></div>
                <button class="nxtrunn-modal-close" aria-label="Close modal">
                    <svg fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <div class="nxtrunn-modal-body">
                    <!-- Content loaded dynamically -->
                </div>
            </div>
        </div>

        <!-- Add Club Modal -->
        <div class="nxtrunn-modal-backdrop" id="nxtrunn-add-club-modal" role="dialog" aria-modal="true" aria-label="Add your run club" style="display: none;">
            <div class="nxtrunn-modal-content nxtrunn-add-modal" style="max-width: 480px;">
                <div class="nxtrunn-modal-drag-handle"></div>
                <button class="nxtrunn-modal-close" id="nxtrunn-close-add-modal" aria-label="Close modal">
                    <svg fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <div class="nxtrunn-modal-body" style="padding-top: 40px;">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <h2>Add Your Run Club</h2>
                        <p>Submit your run club to be featured in the NXTRUNN directory. You'll be redirected to our submission form.</p>
                    </div>
                    <div class="nxtrunn-modal-actions" style="flex-direction: column;">
                        <a href="https://nxtrunn.com/app/add-your-run-club/" class="nxtrunn-submit-link">
                            Go to Submission Form
                        </a>
                        <button class="nxtrunn-cancel-btn" id="nxtrunn-cancel-add-modal">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pace Filter Modal -->
        <div class="nxtrunn-modal-backdrop" id="nxtrunn-pace-modal" role="dialog" aria-modal="true" aria-label="Find my pace group" style="display: none;">
            <div class="nxtrunn-modal-content" style="max-width: 400px;">
                <div class="nxtrunn-modal-drag-handle"></div>
                <button class="nxtrunn-modal-close" id="nxtrunn-pace-close" aria-label="Close modal">
                    <svg fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <div class="nxtrunn-modal-body" style="padding-top: 32px;">
                    <div class="nxtrunn-pace-header">
                        <h3 style="margin: 0 0 4px 0; font-family: var(--font-display); text-transform: uppercase; letter-spacing: 0.5px;">Find My Pace Group</h3>
                        <div class="nxtrunn-units-toggle">
                            <button class="nxtrunn-unit-btn active" data-unit="mi">mi</button>
                            <button class="nxtrunn-unit-btn" data-unit="km">km</button>
                        </div>
                    </div>

                    <div class="nxtrunn-pace-display" style="text-align: center; margin: 20px 0 8px;">
                        <span id="nxtrunn-pace-label" style="font-size: 24px; font-weight: 700; color: var(--color-text-primary);">9:00 - 12:00</span>
                        <span id="nxtrunn-pace-unit-label" style="font-size: 14px; color: var(--color-text-secondary); display: block;">min/mi</span>
                    </div>

                    <div class="nxtrunn-pace-sliders" style="padding: 16px 8px;">
                        <label style="font-size: 12px; color: var(--color-text-secondary);">Slowest pace I want</label>
                        <input type="range" id="nxtrunn-pace-min" min="300" max="1800" step="30" value="540" style="width: 100%;">
                        <label style="font-size: 12px; color: var(--color-text-secondary);">Fastest pace I want</label>
                        <input type="range" id="nxtrunn-pace-max" min="300" max="1800" step="30" value="720" style="width: 100%;">
                    </div>

                    <div style="text-align: center; margin: 12px 0;">
                        <button class="nxtrunn-walker-btn" id="nxtrunn-walker-btn">I'm a walker</button>
                    </div>

                    <div class="nxtrunn-modal-actions" style="flex-direction: row; gap: 12px; margin-top: 16px;">
                        <button class="nxtrunn-cancel-btn" id="nxtrunn-pace-clear" style="flex:1;">Clear</button>
                        <button class="nxtrunn-submit-link" id="nxtrunn-pace-apply" style="flex:1; text-align:center;">Show Clubs</button>
                    </div>
                </div>
            </div>
        </div>

        <?php

        return ob_get_clean();
    }
}
