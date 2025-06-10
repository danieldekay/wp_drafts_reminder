<?php
namespace DanielDeKay\DraftReminder\Services;

class DraftFinder {
    public function get_stale_drafts(): array {
        $settings   = get_option( 'draft_reminder_settings', [] );
        // Ensure default for post_types if not set or empty, similar to sanitize callback
        $post_types = !empty($settings['post_types']) ? $settings['post_types'] : [ 'post' ];
        $age_days   = absint( $settings['age_days'] ?? 7 );
        if ($age_days < 1) {
            $age_days = 7;
        }

        $query_args = [
            'post_type'      => $post_types,
            'post_status'    => 'draft',
            'date_query'     => [
                [
                    'column' => 'post_modified_gmt', // Use GMT for consistency
                    'before' => "$age_days days ago",
                    'inclusive' => false, // Drafts modified *before* this time
                ],
            ],
            'posts_per_page' => -1, // Get all matching drafts
            'fields'         => 'ids', // Optimize by fetching only IDs initially if more processing needed later
                                     // User's spec has 'fields' => [ 'ID', 'post_title', 'post_author' ], reverting to that.
            'no_found_rows'  => true, // Performance optimization
        ];

        // Reverting 'fields' to user's specification for direct use
        $query_args['fields'] = [ 'ID', 'post_title', 'post_author', 'post_modified' ]; // Added post_modified for potential debugging

        $query = new \WP_Query( $query_args );

        $authors_drafts = [];
        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {
                $author_id = (int) $post->post_author;
                $authors_drafts[ $author_id ][] = [
                    'ID'    => $post->ID,
                    'title' => $post->post_title ?: __( '(no title)', 'unpublished-draft-reminder' ),
                    // 'modified' => $post->post_modified // For debugging if needed
                ];
            }
        }
        return $authors_drafts;
    }
}
