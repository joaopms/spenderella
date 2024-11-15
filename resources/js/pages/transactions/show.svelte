<script>
    import { useForm } from "@inertiajs/svelte";
    import { DateTime } from "luxon";

    const { paymentMethods, transactions } = $props();

    const newTForm = useForm({
        date: DateTime.now().toISODate(),
        name: null,
        category: null,
        description: null,
        type: "-",
        amount: null,
        paymentMethod: null,
        // nordigenTransaction: null
    });

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

<svelte:head>
    <title>Transactions - Spenderella</title>
</svelte:head>

<h1>Transactions</h1>

<!-- New transactions -->
<section>
    <h2>New transaction</h2>

    <form onsubmit={newTFormSubmit}>
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
<section>
    <table border="1">
        <thead>
            <tr>
                <th>Date</th>
                <th>Payment Method</th>
                <th>Name</th>
                <th>Category</th>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            {#each transactions.data as { date, paymentMethod, name, category, description, amount }}
                <tr>
                    <td>{date}</td>
                    <td>{paymentMethod.name}</td>
                    <td>{name}</td>
                    <td>{category}</td>
                    <td>{description}</td>
                    <td align="right">{amount}</td>
                </tr>
            {/each}
        </tbody>
    </table>
</section>
