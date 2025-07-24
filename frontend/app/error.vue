<template>
    <div>
        <CommonNavbar :title="''" />
        <main class="container my-4 py-5 d-flex justify-content-center align-items-center">
            <div class="text-center">
                <h2 class="display-1 pb-4">{{ error?.statusCode || '404' }}</h2>
                <button class="btn btn-sm btn-link" @click="handleError">
                    <i class="bi bi-house me-2"></i> Go to Home
                </button>
            </div>
        </main>
        <CommonFooter />
    </div>
</template>

<script setup lang="ts">

import type { NuxtError } from '#app'

const props = defineProps({
    error: Object as () => NuxtError
})

const handleError = () => clearError({ redirect: '/' })

</script>