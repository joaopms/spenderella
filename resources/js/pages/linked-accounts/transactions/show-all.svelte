<script>
    import { inertia, router } from "@inertiajs/svelte";
    import { setPageTitles } from "../../../utils/store.svelte.js";

    setPageTitles(["Transactions", "Linked Accounts"]);
    const {
        linkToTransactionUrl,
        linkNordigenTransactionToTransactionUrl,

        transactions,
        transactionToLink,
    } = $props();

    function linkTransaction(uuid) {
        if (transactionToLink) {
            // Linking a transaction to a Nordigen transaction
            const url = linkNordigenTransactionToTransactionUrl.replace(
                "%uuid%",
                transactionToLink.data.uuid,
            );
            router.post(url, { uuid });
        } else {
            // Linking a Nordigen transaction to a transaction
            router.post(linkToTransactionUrl, { uuid });
        }
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

{#if transactionToLink}
    <p>Linking to transaction</p>
{/if}

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
