<script>
    import { useForm } from "@inertiajs/svelte";
    import { setPageTitles } from "../../utils/store.svelte.js";

    setPageTitles("Settings");
    const { linkedAccounts, paymentMethodTypes, paymentMethods } = $props();

    const pmForm = useForm({
        name: null,
        type: null,
        accountToLink: null,
    });

    function pmFormReset(e) {
        e.preventDefault();
        $pmForm.reset();
    }

    function pmFormSubmit(e) {
        e.preventDefault();
        $pmForm.post("/settings/payment-method", {
            preserveScroll: true,
            onSuccess: () => $pmForm.reset(),
        });
    }
</script>

<h1>Settings</h1>

<!-- Linked Accounts -->
<section>
    <h2>Linked Accounts</h2>

    <table border="1">
        <thead>
            <tr>
                <th>Name</th>
                <th>Institution</th>
                <th>IBAN</th>
            </tr>
        </thead>
        <tbody>
            {#each linkedAccounts.data as { name, institutionName, iban }}
                <tr>
                    <td>{name}</td>
                    <td>{institutionName}</td>
                    <td>{iban}</td>
                </tr>
            {/each}
        </tbody>
    </table>
</section>

<!-- Payment Methods -->
<section>
    <h2>Payment Methods</h2>

    <table border="1">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Linked Account</th>
            </tr>
        </thead>
        <tbody>
            {#each paymentMethods.data as { name, type, linkedAccount }}
                <tr>
                    <td>{name}</td>
                    <td>{type}</td>
                    <td>{linkedAccount ? linkedAccount.name : "None"}</td>
                </tr>
            {/each}
        </tbody>
    </table>

    <h3>Add new payment method</h3>
    <form onsubmit={pmFormSubmit}>
        <div>
            <label for="pm_name">Name</label>
            <input type="text" id="pm_name" bind:value={$pmForm.name} />
            {#if $pmForm.errors.name}
                <div class="form-error">{$pmForm.errors.name}</div>
            {/if}
        </div>

        <div>
            <label for="pm_type">Type</label>
            <select id="pm_type" bind:value={$pmForm.type}>
                <option value={null} disabled hidden>Select one</option>
                {#each Object.entries(paymentMethodTypes) as [key, name]}
                    <option value={key}>{name}</option>
                {/each}
            </select>
            {#if $pmForm.errors.type}
                <div class="form-error">{$pmForm.errors.type}</div>
            {/if}
        </div>

        <div>
            <label for="pm_accountToLink">Account to link</label>
            <select id="pm_accountToLink" bind:value={$pmForm.accountToLink}>
                <option value={null}>None</option>
                <optgroup label="Accounts">
                    {#each linkedAccounts.data as account}
                        <option value={account.uuid}>
                            {account.name}
                        </option>
                    {/each}
                </optgroup>
            </select>
            {#if $pmForm.errors.accountToLink}
                <div class="form-error">{$pmForm.errors.accountToLink}</div>
            {/if}
        </div>

        <button
            type="reset"
            disabled={!$pmForm.isDirty || $pmForm.processing}
            onclick={pmFormReset}
        >
            Clear
        </button>
        <button type="submit" disabled={$pmForm.processing}>Submit</button>
    </form>
</section>
