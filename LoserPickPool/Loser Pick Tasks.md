---

kanban-plugin: basic

---

## Backlog

- [ ] Switch everything to grid or flex layout
- [ ] Two tables: One for entries still alive, one for the rest
- [ ] Redo SQL backend<br>- Use constraints (reference user table, NOT NULL, etc.)
- [ ] Make visual diagram of code organization.  <br>Will help for adding more complexity, and identifying currently existing spaghetti
- [ ] Sort tables
- [ ] Suggestion box
- [ ] Confirmation that they would like to overwrite a previous pick
- [ ] Look into ways to refactor code. Could probably be neater.
- [ ] Random funny comment on someone's pick


## To Do



## In Progress



## Completed

- [ ] Update to work in 2024
- [ ] Update get_current_week logic
- [ ] New table of bye weeks/TNF


## 2023 (MVP)

- [ ] Only list teams that are available for that user
- [ ] Mark correct and incorrect picks from previous weeks
- [ ] First and Last Name with Username
- [ ] Hide picks button on View Picks page
- [ ] Show username in dropdown immediately after it's registered. Reloading is bad for user experience
- [ ] Re-implement drop-down list, verify the username and team inside
- [ ] Check PIN for making a pick
- [ ] List a user's picks to them
- [ ] Register Username with a PIN
- [ ] Week function: disable picks Sunday and Monday
- [ ] Week function: don't show current picks until they are locked on Sunday
- [ ] Show list of picks all the time
- [ ] Move server and db info out of main files. Now just in connection info file that I never need to rewrite, and can be saved outside of the repo
- [ ] Automatically set week
- [ ] Week function: only pick for current week
- [ ] Week function: disable Thursday night teams
- [ ] Cannot pick team that is already in your pick list. Still available in drop down for now
- [ ] Switch ph_get_picks_table_html to use the new array of users picks. Less SQL requests because I won't be requesting one week at a time
- [ ] Get Users picks: associative array of week to team
- [ ] Rework SqlAccessController to have clearer returns.<br>Should not return mysql objects, just arrays or single values to outside calls. Internal can pass around mysqli objects.
- [ ] Overwrite previous pick if username and week match
- [ ] Delete all picks
- [ ] Refactor router:<br>Move User Handling out to own file
- [ ] Refactor router:<br>Move Pick Handling out to own file
- [ ] Display table with weeks as own columns, instead of a week column that has a value. Value of individual week columns will be the team that user picked in that week




%% kanban:settings
```
{"kanban-plugin":"basic"}
```
%%