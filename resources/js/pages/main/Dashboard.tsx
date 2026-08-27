import { usePage } from "@inertiajs/react"
import type { User } from "@/types";

export default function Dashboard() {
    const { user } = usePage<User>().props.auth;
    return (
        <div>
            {user.email}
        </div>
    )
}