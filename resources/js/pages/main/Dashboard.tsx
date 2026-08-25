import type { User } from "@/types";
import { usePage } from "@inertiajs/react"

export default function Dashboard() {
    const { user } = usePage<User>().props.auth;
    return (
        <div>
            {user.email}
        </div>
    )
}