# Web backend contract (implementation in progress)

Frontend: GET /?organization=SLUG&tab=dashboard|projects|incidents|events|organizations|settings renders workspace/Index. Also /dashboard, /projects, /incidents, /events, /organizations, /settings. Optional ?organization=SLUG selects tenant; otherwise first membership. GET /organization/SLUG and /organization/SLUG/projects select that tenant.

Props: organizations [{id,name,slug}], organization nullable with members [{id,name,email,role}], projects (status,last_checked_at), incidents, events, activeTab. Additional permissions on organization and project control mutations. GET /organization/SLUG/project/SLUG renders project/Show: project, organization, events, checks, incidents, rules.

Mutations return redirects with validation errors and flash.message. POST /register {name,email,password,password_confirmation}, POST /login {email,password,remember?}, POST /logout. GET /login and /register render auth/Login and auth/Register. Session CSRF applies to web mutations.

POST /organization {name}; PATCH or POST /organization/SLUG {name}; DELETE /organization/SLUG; POST /organization/SLUG/restore. POST /organization/SLUG/members {email,role:owner|project-manager|member}; DELETE /organization/SLUG/members/USER_ID. Only owners manage organization/members; primary owner cannot be removed/demoted. Owners and project-managers create/update projects; owners delete projects.

POST /organization/SLUG/project {name,description?,base_url?,check_status_url?,active?, monitoring config}; PATCH or POST /organization/SLUG/project/SLUG same fields; DELETE same URL. POST .../rotate-token and .../rotate-github-secret expose flash.api_token / flash.github_secret exactly once. Tokens stored as SHA256; webhook secrets encrypted and hidden.

Monitoring agent coordination: web agent owns existing Project model (casts/relations) and bootstrap/app.php. Please communicate migration fields and new model names in .codex/monitoring-contract.md before integration. No migrations against user database. Main owns frontend and permanent docs; this scratch contract is the handoff.

Main explicitly expanded scope: web agent handles rules/incident routes in ProjectController. POST .../rules {name,event_type,threshold,window_seconds,severity,enabled}; POST .../rules/RULE_ID same fields; DELETE .../rules/RULE_ID. POST .../incidents {title,severity}; POST .../incidents/INCIDENT_ID/status {status:investigating|identified|monitoring|resolved}; POST .../incidents/INCIDENT_ID/notes {body}. GET .../incidents/INCIDENT_ID renders incident/Show with incident,project,organization,events,notes.

Hume: please add incident_notes {id,incident_id,user_id,body,timestamps} and IncidentNote model; project configuration columns check_interval_seconds,timeout_seconds,failure_threshold,latency_threshold_ms (or document your preferred names). These are needed for requested frontend forms. Existing monitoring migration observed; awaiting schema agreement before wiring config/notes.

11:18 implementation status: routes above are live. Seven SQLite tests pass (157 assertions), including real CSRF enforcement, auth/throttles, role restrictions, tenant binding, secrets flash. Remaining Hume integration: notes model/table and project config columns. IncidentDetector::refreshProjectStatus exists; web lifecycle will call it. Main can build all forms above now. organization.permissions keys update/delete/manageMembers/createProject; project.permissions update/delete. project has has_api_token/has_github_secret booleans, never secret values. archivedOrganizations [{id,name,slug,deleted_at}] enables restore UI. Membership id is USER ID, not pivot ID. Successful forms redirect, errors are standard Inertia validation errors.
