import "./bootstrap";

import { createInertiaApp } from "@inertiajs/svelte";
import { mount } from "svelte";

import layout from "./layout.svelte";

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob("./pages/**/*.svelte", { eager: true });
        const page = pages[`./pages/${name}.svelte`];

        return { default:page.default, layout: page.layout || layout };
    },
    setup({ el, App, props, plugin }) {
        mount(App, { target: el, props });
    },
});
