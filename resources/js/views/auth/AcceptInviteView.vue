<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { invitationService } from '@/services/invitationService'
import { ValidationError } from '@/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { useAuthStore } from '@/stores/auth'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()

const token = route.params.token as string

const email = ref('')
const name = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const errors = ref<Record<string, string[]>>({})
const isLoading = ref(false)
const isLoadingInvite = ref(true)
const invalidToken = ref(false)

onMounted(async () => {
  try {
    const invite = await invitationService.show(token)
    email.value = invite.email
  } catch {
    invalidToken.value = true
  } finally {
    isLoadingInvite.value = false
  }
})

const submit = async () => {
  errors.value = {}
  isLoading.value = true

  try {
    const user = await invitationService.accept(token, {
      name: name.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })

    authStore.setUser(user)
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
  <div class="flex min-h-screen items-center justify-center px-4">
    <Card class="w-full max-w-sm">
      <CardHeader>
        <CardTitle class="text-2xl">{{ t('invite.accept.title') }}</CardTitle>
        <CardDescription>{{ t('invite.accept.description') }}</CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="isLoadingInvite" class="py-8 text-center text-sm text-muted-foreground">
          {{ t('invite.accept.loading') }}
        </div>

        <div v-else-if="invalidToken" class="py-8 text-center">
          <p class="text-sm font-medium mb-1">{{ t('invite.accept.invalid') }}</p>
          <p class="text-xs text-muted-foreground">{{ t('invite.accept.invalidHint') }}</p>
        </div>

        <form v-else @submit.prevent="submit" class="grid gap-4">
          <div class="grid gap-2">
            <Label for="email">{{ t('invite.accept.email') }}</Label>
            <Input id="email" :model-value="email" type="email" disabled />
          </div>

          <div class="grid gap-2">
            <Label for="name">{{ t('invite.accept.name') }}</Label>
            <Input id="name" v-model="name" type="text" required autocomplete="name" />
            <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name[0] }}</p>
          </div>

          <div class="grid gap-2">
            <Label for="password">{{ t('invite.accept.password') }}</Label>
            <Input id="password" v-model="password" type="password" required autocomplete="new-password" />
            <p v-if="errors.password" class="text-sm text-destructive">{{ errors.password[0] }}</p>
          </div>

          <div class="grid gap-2">
            <Label for="password-confirmation">{{ t('invite.accept.confirmPassword') }}</Label>
            <Input id="password-confirmation" v-model="passwordConfirmation" type="password" required autocomplete="new-password" />
          </div>

          <Button type="submit" class="w-full" :disabled="isLoading">
            {{ isLoading ? t('invite.accept.submitting') : t('invite.accept.submit') }}
          </Button>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
