<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { ValidationError } from '@/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useI18n } from 'vue-i18n'

const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()

const email = ref('')
const password = ref('')
const errors = ref<Record<string, string[]>>({})
const isLoading = ref(false)

const submit = async () => {
  errors.value = {}
  isLoading.value = true

  try {
    await authStore.login(email.value, password.value)
    router.push({ name: 'time' })
  } catch (error) {
    if (error instanceof ValidationError) {
      errors.value = error.errors
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center px-4 bg-gradient-to-br from-primary/5 via-background to-accent/30">
    <Card class="w-full max-w-sm shadow-lg border-t-4 border-t-primary">
      <CardHeader class="text-center">
        <CardTitle class="text-2xl">{{ t('auth.login.title') }}</CardTitle>
        <CardDescription>{{ t('auth.login.description') }}</CardDescription>
      </CardHeader>
      <CardContent>
        <form @submit.prevent="submit" class="grid gap-4">
          <div class="grid gap-2">
            <Label for="email">{{ t('auth.login.email') }}</Label>
            <Input
              id="email"
              v-model="email"
              type="email"
              placeholder="you@example.com"
              required
              autofocus
            />
            <p v-if="errors.email" class="text-sm text-destructive">{{ errors.email[0] }}</p>
          </div>

          <div class="grid gap-2">
            <Label for="password">{{ t('auth.login.password') }}</Label>
            <Input
              id="password"
              v-model="password"
              type="password"
              required
            />
            <p v-if="errors.password" class="text-sm text-destructive">{{ errors.password[0] }}</p>
          </div>

          <Button type="submit" class="w-full" :disabled="isLoading">
            {{ isLoading ? t('auth.login.submitting') : t('auth.login.submit') }}
          </Button>

          <div class="text-center text-sm">
            <RouterLink :to="{ name: 'forgot-password' }" class="text-muted-foreground underline-offset-4 hover:underline">
              {{ t('auth.login.forgotPassword') }}
            </RouterLink>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
