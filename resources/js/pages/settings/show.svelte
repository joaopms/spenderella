<script>
    const { linkedAccounts } = $props();
</script>

<svelte:head>
    <title>Settings - Spenderella</title>
</svelte:head>

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
            {#each linkedAccounts.data as account}
                <tr>
                    <td>
                        {account.name}
                    </td>
                    <td>
                        {account.institutionName}
                    </td>
                    <td>
                        {account.iban}
                    </td>
                </tr>
            {/each}
        </tbody>
    </table>
</section>
