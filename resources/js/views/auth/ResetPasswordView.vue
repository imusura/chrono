<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { authService } from '@/services/authService'
import { ValidationError } from '@/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'

const route = useRoute()

const email = ref((route.query.email as string) ?? '')
const password = ref('')
const passwordConfirmation = ref('')
const errors = ref<Record<string, string[]>>({})
const successMessage = ref('')
const isLoading = ref(false)

const submit = async () => {
  errors.value = {}
  successMessage.value = ''
  isLoading.value = true

  try {
    successMessage.value = await authService.resetPassword({
      token: route.params.token as string,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })
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
        <CardTitle class="text-2xl">Reset Password</CardTitle>
        <CardDescription>Enter your new password</CardDescription>
      </CardHeader>
      <CardContent>
        <Alert v-if="successMessage" class="mb-4">
          <AlertDescription>
            {{ successMessage }}
            <RouterLink :to="{ name: 'login' }" class="underline underline-offset-4">
              Back to login
            </RouterLink>
          </AlertDescription>
        </Alert>

        <form @submit.prevent="submit" class="grid gap-4">
          <div class="grid gap-2">
            <Label for="email">Email</Label>
            <Input
              id="email"
              v-model="email"
              type="email"
              required
            />
            <p v-if="errors.email" class="text-sm text-destructive">{{ errors.email[0] }}</p>
          </div>

          <div class="grid gap-2">
            <Label for="password">New Password</Label>
            <Input
              id="password"
              v-model="password"
              type="password"
              required
            />
            <p v-if="errors.password" class="text-sm text-destructive">{{ errors.password[0] }}</p>
          </div>

          <div class="grid gap-2">
            <Label for="password-confirmation">Confirm Password</Label>
            <Input
              id="password-confirmation"
              v-model="passwordConfirmation"
              type="password"
              required
            />
          </div>

          <Button type="submit" class="w-full" :disabled="isLoading">
            {{ isLoading ? 'Resetting...' : 'Reset Password' }}
          </Button>

          <div class="text-center text-sm">
            <RouterLink :to="{ name: 'login' }" class="text-muted-foreground underline-offset-4 hover:underline">
              Back to login
            </RouterLink>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
