<?php
namespace DanielDeKay\DraftReminder\Services;

// Ensure Services path is correct if these are in the same dir.
// use DanielDeKay\DraftReminder\Services\DraftFinder; // Not strictly needed if in same namespace
// use DanielDeKay\DraftReminder\Services\Mailer;    // Not strictly needed if in same namespace

class EmailJob {
    private DraftFinder $draft_finder;
    private Mailer $mailer;

    public function __construct( DraftFinder $draft_finder, Mailer $mailer ) {
        $this->draft_finder = $draft_finder;
        $this->mailer = $mailer;
    }

    /**
     * Executes the job to find stale drafts and send reminder emails.
     */
    public function execute(): array {
        $stale_drafts_by_author = $this->draft_finder->get_stale_drafts();
        $authors_emailed = 0;
        $drafts_found = 0;

        if ( empty( $stale_drafts_by_author ) ) {
            // Optional: Log that no stale drafts were found.
            // error_log('Draft Reminder: No stale drafts found to process.');
            return ['authors' => 0, 'posts' => 0];
        }

        foreach ( $stale_drafts_by_author as $author_id => $drafts ) {
            if ( empty( $drafts ) ) {
                continue;
            }
            // Ensure author_id is an integer
            $author_id = (int) $author_id;

            $this->mailer->send( $author_id, $drafts );
            $authors_emailed++;
            $drafts_found += count($drafts);
        }

        // This return will be useful for the Logger in Step 5
        return ['authors' => $authors_emailed, 'posts' => $drafts_found];
    }
}

// Example of how this might be instantiated and run (e.g., in a WP Cron callback)
// This part is conceptual for now and would typically be in the main plugin file or a dedicated cron scheduler class.
/*
function my_daily_draft_reminder_event() {
    // Dependency Injection is preferred if you have a container or service locator.
    // For simplicity here, direct instantiation:
    $draft_finder = new \DanielDeKay\DraftReminder\Services\DraftFinder();
    $mailer       = new \DanielDeKay\DraftReminder\Services\Mailer();
    $email_job    = new \DanielDeKay\DraftReminder\Services\EmailJob( $draft_finder, $mailer );

    $result = $email_job->execute();

    // Optional: Log the result using the Logger service from Step 5
    // $logger = new \DanielDeKay\DraftReminder\Services\Logger();
    // $logger->record_run($result);
}
// add_action( 'my_daily_draft_reminder_cron', 'my_daily_draft_reminder_event' );
*/
