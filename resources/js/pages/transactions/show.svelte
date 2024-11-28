<script>
    import { useForm } from "@inertiajs/svelte";
    import { DateTime } from "luxon";
    import { setPageTitles } from "../../utils/store.svelte.js";

    setPageTitles("Transactions");
    const { paymentMethods, transactions, transactionToLink = null } = $props();

    const newTForm = useForm({
        transactionToLink: null,
        parentTransaction: null,
        date: DateTime.now().toISODate(),
        name: null,
        category: null,
        description: null,
        type: "-",
        amount: null,
        paymentMethod: null,
        // nordigenTransaction: null
    });

    // Pre-fill the form when linking to a transaction from a linked account
    if (transactionToLink) {
        const data = transactionToLink.data;

        $newTForm.transactionToLink = data.uuid;
        $newTForm.date = data.date;
        $newTForm.name = data.description;
        $newTForm.amount = Math.abs(data.amountRaw) / 100;
        $newTForm.type = data.amountRaw > 0 ? "+" : "-";
    }

    function addSplitTransaction(transaction) {
        $newTForm.parentTransaction = transaction.uuid;
        $newTForm.date = transaction.date;
        $newTForm.type = "+";
    }

    function newTFormReset(e) {
        e.preventDefault();
        $newTForm.reset();
    }

    function newTFormSubmit(e) {
        e.preventDefault();
        $newTForm
            .transform(function (data) {
                const { type, amount, ...newData } = data;
                newData.amount = Math.abs(amount * 100);

                // If the amount is negative, multiply by -1 to make it negative
                if (type === "-") {
                    newData.amount *= -1;
                }

                return newData;
            })
            .post("/transactions", {
                preserveScroll: true,
                // onSuccess: () => $newTForm.reset(),
            });
    }
</script>

<h1>Transactions</h1>

<!-- New transactions -->
<section>
    <h2>New transaction</h2>
    {#if $newTForm.parentTransaction}
        <p>Adding child transaction to {$newTForm.parentTransaction}</p>
    {/if}
    {#if $newTForm.transactionToLink}
        <p>Linking to transaction</p>
    {/if}

    <form onsubmit={newTFormSubmit}>
        {#if $newTForm.errors.parentTransaction}
            <div class="form-error">{$newTForm.errors.parentTransaction}</div>
        {/if}
        {#if $newTForm.errors.transactionToLink}
            <div class="form-error">{$newTForm.errors.transactionToLink}</div>
        {/if}

        <div>
            <label for="newT_date">Date</label>
            <input type="date" id="newT_date" bind:value={$newTForm.date} />
            {#if $newTForm.errors.date}
                <div class="form-error">{$newTForm.errors.date}</div>
            {/if}
        </div>

        <div>
            <label for="newT_name">Name</label>
            <input type="text" id="newT_name" bind:value={$newTForm.name} />
            {#if $newTForm.errors.name}
                <div class="form-error">{$newTForm.errors.name}</div>
            {/if}
        </div>

        <!-- Hide fields that are only for parent transactions -->
        {#if !$newTForm.parentTransaction}
            <div>
                <label for="newT_category">Category</label>
                <input
                    type="text"
                    id="newT_category"
                    bind:value={$newTForm.category}
                />
                {#if $newTForm.errors.category}
                    <div class="form-error">{$newTForm.errors.category}</div>
                {/if}
            </div>

            <div>
                <label for="newT_description">Description</label>
                <input
                    type="text"
                    id="newT_description"
                    bind:value={$newTForm.description}
                />
                {#if $newTForm.errors.description}
                    <div class="form-error">{$newTForm.errors.description}</div>
                {/if}
            </div>

            <div>
                <fieldset>
                    <legend>Type</legend>

                    <input
                        type="radio"
                        id="newT_type-expense"
                        name="type"
                        value="-"
                        bind:group={$newTForm.type}
                    />
                    <label for="newT_type-expense">Expense</label>

                    <input
                        type="radio"
                        id="newT_type-income"
                        name="type"
                        value="+"
                        bind:group={$newTForm.type}
                    />
                    <label for="newT_type-income">Income</label>
                </fieldset>
            </div>
        {/if}

        <div>
            <label for="newT_amount">Amount</label>
            <input
                type="number"
                id="newT_amount"
                step="0.01"
                min="0.01"
                bind:value={$newTForm.amount}
            />
            {#if $newTForm.errors.amount}
                <div class="form-error">{$newTForm.errors.amount}</div>
            {/if}
        </div>

        <div>
            <label for="newT_paymentMethod">Payment Method</label>
            <select
                id="newT_paymentMethod"
                bind:value={$newTForm.paymentMethod}
            >
                <option value={null} disabled hidden>Select one</option>
                {#each paymentMethods.data as { uuid, name }}
                    <option value={uuid}>{name}</option>
                {/each}
            </select>
            {#if $newTForm.errors.paymentMethod}
                <div class="form-error">{$newTForm.errors.paymentMethod}</div>
            {/if}
        </div>

        <button
            type="reset"
            disabled={!$newTForm.isDirty || $newTForm.processing}
            onclick={newTFormReset}
        >
            Clear
        </button>
        <button type="submit" disabled={$newTForm.processing}>Submit</button>
    </form>
</section>

<hr />

<!-- Transactions list -->
{#snippet transactionList(transactions, isSplit = false)}
    <table border="1">
        <thead>
            <tr>
                <th>Date</th>
                <th>Payment Method</th>
                <th>Name</th>
                {#if !isSplit}
                    <th>Category</th>
                    <th>Description</th>
                {/if}
                <th>Amount</th>
                <th>Linked</th>
                <!-- Add split transactions -->
                {#if !isSplit}
                    <th></th>
                {/if}
            </tr>
        </thead>
        <tbody>
            {#each transactions as transaction}
                <!-- Transaction -->
                <tr>
                    <td>{transaction.date}</td>
                    <td>{transaction.paymentMethod.name}</td>
                    <td>{transaction.name}</td>
                    {#if !isSplit}
                        <td>{transaction.category}</td>
                        <td>{transaction.description}</td>
                    {/if}
                    <td align="right">
                        <span title="Debited amount: {transaction.amount}">
                            {transaction.amountAfterSplit}
                        </span>
                    </td>
                    <td>
                        {transaction.linkedTransactionUuid ? "Yes" : "No"}
                    </td>
                    {#if !isSplit}
                        <td>
                            <button
                                title="Add split"
                                onclick={() => addSplitTransaction(transaction)}
                            >
                                +
                            </button>
                        </td>
                    {/if}
                </tr>

                <!-- Splits -->
                {#if !isSplit && transaction.split.length > 0}
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="5">
                            {@render transactionList(transaction.split, true)}
                        </td>
                        <td></td>
                    </tr>
                {/if}
            {/each}
        </tbody>
    </table>
{/snippet}

<section>
    {@render transactionList(transactions.data)}
</section>
