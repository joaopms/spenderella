export const store = $state({
    pageTitles: [],
});

export function setPageTitles(titles) {
    let titlesArray = titles;

    if (!Array.isArray(titles)) {
        titlesArray = [titles];
    }

    store.pageTitles = titlesArray;
}
