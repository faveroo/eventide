export type Organization = {
    id: number;
    name: string;
    slug: string;
    owner_id?: number;
    permissions?: {
        update: boolean;
        delete: boolean;
        manageMembers: boolean;
        createProject: boolean;
    };
    can_manage?: boolean;
    memberships?: Membership[];
    members?: Membership[];
};
export type Membership = {
    id: number;
    user_id?: number;
    user?: { name: string; email: string };
    name?: string;
    email?: string;
    role?: { name: string } | string;
};
export type Project = {
    id: number;
    name: string;
    slug: string;
    description?: string;
    status: string;
    active: boolean;
    base_url?: string;
    check_status_url?: string;
    last_checked_at?: string;
    last_latency_ms?: number;
    check_interval_seconds?: number;
    timeout_seconds?: number;
    failure_threshold?: number;
    latency_threshold_ms?: number;
    consecutive_failures?: number;
    organization?: Organization;
    checks?: Check[];
    health_checks?: Check[];
    can_manage?: boolean;
    permissions?: { update: boolean; delete: boolean };
    has_api_token?: boolean;
    has_github_secret?: boolean;
};
export type Check = {
    id: number;
    status?: string;
    successful?: boolean;
    success?: boolean;
    latency_ms?: number;
    response_time_ms?: number;
    response_status?: number;
    status_code?: number;
    checked_at?: string;
    created_at: string;
    error?: string;
};
export type AppEvent = {
    id: number;
    type: string;
    level?: string;
    severity?: string;
    message?: string;
    source?: string;
    fingerprint?: string;
    payload?: Record<string, unknown>;
    metadata?: Record<string, unknown>;
    occurred_at?: string;
    created_at: string;
    project?: Project;
    incident_id?: number;
};
export type Incident = {
    id: number;
    title: string;
    status: string;
    severity: string;
    opened_at?: string;
    resolved_at?: string;
    created_at: string;
    project?: Project;
    project_id: number;
    description?: string;
    updates?: {
        id: number;
        body?: string;
        message?: string;
        status?: string;
        created_at: string;
        user?: { name: string };
    }[];
};
export type Rule = {
    id: number;
    name: string;
    event_type?: string;
    threshold: number;
    window_seconds?: number;
    window_minutes?: number;
    severity: string;
    enabled: boolean;
};
export type Collection<T> =
    | T[]
    | {
          data: T[];
          current_page?: number;
          last_page?: number;
          links?: { url: string | null; label: string; active: boolean }[];
      };
export function rows<T>(value?: Collection<T>): T[] {
    return Array.isArray(value) ? value : (value?.data ?? []);
}
