<script>
    import { inertia, router } from "@inertiajs/svelte";
    import { setPageTitles } from "../../../utils/store.svelte.js";

    setPageTitles(["Transactions", "Linked Accounts"]);
    const { transactions } = $props();

    function linkTransaction(uuid) {
        router.post("/transactions/link", { uuid });
    }
</script>

{#snippet pagination()}
    {@const meta = transactions.meta}

    <section>
        {#each meta.links as link}
            {#if link.url}
                [
                <a href={link.url} use:inertia={{ only: ["transactions"] }}>
                    {@html link.label}
                </a>
                ]
            {:else}
                <span>[{@html link.label}]</span>
            {/if}
        {/each}

        {meta.to} / {meta.total}
    </section>
{/snippet}

<h1>Linked Accounts</h1>

<h2>Transactions</h2>

<!-- Transaction list -->
<section>
    <!-- Pagination -->
    {@render pagination()}

    <table border="1">
        <thead>
            <tr>
                <th>Date</th>
                <th>Account</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Linked</th>
            </tr>
        </thead>
        <tbody>
            {#each transactions.data as transaction}
                <!-- Transaction -->
                <tr>
                    <td>{transaction.date}</td>
                    <td>{transaction.account.name}</td>
                    <td>{transaction.description}</td>
                    <td>{transaction.amount}</td>
                    <td>
                        <button
                            onclick={() => linkTransaction(transaction.uuid)}
                        >
                            Link
                        </button>

                        {(transaction.linkedTransactionsUuid ?? []).length}
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>

    <!-- Pagination -->
    {@render pagination()}
</section>
