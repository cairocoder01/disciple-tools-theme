### Task Summary
Fix the contact list filter inconsistency where clearing all custom filters causes the list to reset to an unfiltered state (including archived contacts) instead of the expected default state (non-archived contacts only).

### Files to Modify
- dt-assets/js/modular-list.js — investigate how the frontend state management handles clearing filters.
- dt-contacts/contacts.js — investigate how the contact list specific filter state is managed and reset.
- dt-contacts/base-setup.php — investigate how the backend handles the REST request query parameters to ensure default filters (like excluding archived) are reapplied when custom filters are cleared.

### Implementation Plan
1.  Analyze `dt-assets/js/modular-list.js` and `dt-contacts/contacts.js` to understand how the filter object is updated and sent when a filter tag is removed via the UI ("x" button).
2.  Determine if the frontend is correctly re-applying the default filters (`overall_status` not including 'archived') when the last custom filter is removed.
3.  Investigate `dt-contacts/base-setup.php` to verify how the REST endpoint handles the incoming query array. Ensure that if no custom filters are present, the default filter (`overall_status` != 'archived') is explicitly set or maintained.
4.  Modify the appropriate file(s) (likely the JS side to ensure the full query state is sent correctly, or the backend side to ensure the default state is enforced if the client fails to do so) to guarantee that clearing the last custom filter results in the correct default state.
5.  Verify the "Show Archived" toggle still functions as intended.

### Acceptance Criteria
- [ ] PHP lint passes on all modified files
- [ ] PHPCS reports zero violations on modified files
- [ ] `WP_MULTISITE=1 vendor/bin/phpunit` passes
- [ ] Clearing all custom filters in the contact list reverts the view to only show non-archived contacts.
- [ ] The "Show Archived" toggle correctly overrides the default filter when enabled.

### Edge Cases & Constraints
- Do not modify `functions.php`.
- Do not drop or alter existing database tables.
- Do not remove existing PHPUnit tests.
- Ensure the fix handles cases where multiple custom filters are applied and only one is cleared, as well as when the last one is cleared.
