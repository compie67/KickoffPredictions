# KickoffPredictions for OSSN

Generic file-based prediction pool for OSSN. No database changes.

## Important install flow

1. Upload the ZIP in the OSSN component installer.
2. Go to Components and enable **KickoffPredictions**.
3. Open the normal OSSN admin configure page:

```text
/administrator/component/KickoffPredictions
```

The frontend routes only work after enabling the component:

```text
/kickoff
/kickoff/matches
/kickoff/bonus
/kickoff/leaderboard
```

## Storage

All runtime data is stored under:

```text
ossn_data/components/KickoffPredictions/
```

## Templates

Included starter templates:

- `worldcup2026`
- `womensworldcup`
- `hockeycup`
- `formula1`

These are seed/demo templates and can be replaced later with real tournament files.


## v1.5

- Added the frontend prediction pool link to the left OSSN newsfeed menu under Links.
- Removed the frontend topbar dropdown link to keep the user interface cleaner.


## Version 2.1
- Fixed TheSportsDB CSV timestamps: strTimestamp is treated as UTC and converted to Europe/Amsterdam before saving kickoff times.

## Version 2.0

- Cleaner display names for TheSportsDB country-code exports.
- Flags are supported for teams/competitors.
- Admin edit screens for teams/competitors and matches/events.
- Admin result filters by group/date/status.
- User match filters: all, open, locked, filled.
- Reset button for test predictions per pool; results and imported data stay intact.


## v2.5 notes

- Formula 1 seed updated with a fuller 2026 calendar including sprint races.
- Admin can add new competitors/drivers without editing JSON by hand.
- For pick-winner pools, updated competitor lists sync to all events.
- Admin can add extra events/matches from the edit screen.


## v2.6 notes

- Formula 1 seed updated to the user-provided 2026 grid, including Cadillac and without Yuki Tsunoda.
- F1 driver flags/country codes are normalized: `nl`/`NL` displays as 🇳🇱.
- Admin team/competitor editor now includes constructor/team and country/code fields.
- Pick-winner event editor no longer shows the long competitors JSON textarea; drivers are synced centrally from the competitor list.
- Existing F1 data can be refreshed through Teams/competitors opnieuw importeren + Wedstrijden/events opnieuw importeren.


## v2.7 notes

- Uploaded Formula 1 schedule JSON imports are now supported.
- Supports column-oriented JSON exports with fields such as event_format, session3/session3_date and session5/session5_date.
- Testing, practice and qualifying sessions are ignored for prediction events.
- Sprint weekends create a Sprint prediction event and a Race prediction event.
- Local event times with gmt_offset are converted to Europe/Amsterdam before saving.
- Formula 1 seed matches regenerated from the supplied schedule JSON.
