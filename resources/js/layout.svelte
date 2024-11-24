<script>
    import { inertia } from "@inertiajs/svelte";
    import { store } from "./utils/store.svelte.js";

    const { children } = $props();

    const pages = {
        Transactions: "/transactions",
        "Transactions (Linked Accounts)": "/linked-accounts/transactions",
        Settings: "/settings",
    };
</script>

<svelte:head>
    <title>{[...store.pageTitles, "Spenderella"].join(" • ")}</title>
</svelte:head>

{#each Object.entries(pages) as [name, url]}
    <span class="link-wrapper">
        <a use:inertia href={url}>{name}</a>
    </span>
{/each}

{@render children()}

<style>
    a {
        display: inline-flex;
    }

    .link-wrapper {
        font-size: 0.75rem;

        &:not(:last-of-type):after {
            content: "•";
            margin-left: 0.5rem;
            margin-right: 0.5rem;
        }
    }
</style>
