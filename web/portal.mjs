import {
  acceptInvite, getUser, handleAuthCallback, login, logout, requestPasswordRecovery, signup, updateUser,
} from '@netlify/identity';

function notice(target, text, kind) {
  target.className = `alert alert-${kind}`;
  target.textContent = text;
  target.hidden = false;
}

function submitOnce(form, action) {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    try {
      await action(new FormData(form));
    } finally {
      button.disabled = false;
    }
  });
}

async function handleCallback() {
  const main = document.querySelector('main');
  const status = document.createElement('p');
  status.className = 'visually-hidden';
  status.setAttribute('role', 'status');
  main.prepend(status);
  try {
    const callback = await handleAuthCallback();
    if (!callback) return;
    if (callback.type !== 'recovery' && callback.type !== 'invite') {
      window.location.replace('/dashboard.php');
      return;
    }

    main.innerHTML = `<div class="container py-5"><section class="kt-card-flat p-4 kt-form-card mx-auto"><h1 class="h3">${callback.type === 'invite' ? 'Accept your invitation' : 'Set a new password'}</h1><p data-auth-notice role="status" hidden></p><form method="post"><label class="d-block mb-3"><span class="form-label d-block">New password</span><input type="password" name="password" class="form-control" minlength="8" maxlength="150" autocomplete="new-password" required></label><button type="submit" class="btn btn-kt-primary">Save password</button></form></section></div>`;
    submitOnce(main.querySelector('form'), async (fields) => {
      try {
        if (callback.type === 'invite') await acceptInvite(callback.token, fields.get('password'));
        else await updateUser({ password: fields.get('password') });
        window.location.replace('/dashboard.php');
      } catch {
        notice(main.querySelector('[data-auth-notice]'), 'The password could not be saved. Check that it is at least 8 characters and that the link is still valid.', 'danger');
      }
    });
  } catch {
    notice(status, 'This sign-in link is invalid or has expired. Request a new link and try again.', 'danger');
  }
}

for (const form of document.querySelectorAll('form[data-auth]')) {
  submitOnce(form, async (fields) => {
    const feedback = form.parentElement.querySelector('[data-auth-notice]');
    const email = fields.get('email');
    const password = fields.get('password');
    try {
      switch (form.dataset.auth) {
        case 'signup': {
          const user = await signup(email, password, { full_name: fields.get('full_name') });
          if (user.confirmedAt && await getUser()) window.location.assign('/dashboard.php');
          else notice(feedback, 'Check your inbox for an email to confirm your account.', 'success');
          break;
        }
        case 'recover':
          await requestPasswordRecovery(email);
          notice(feedback, 'If an account exists for that address, check your inbox for a recovery link.', 'success');
          break;
        case 'change': {
          const user = await getUser();
          if (!user?.email) throw new Error('Not signed in');
          await login(user.email, fields.get('current_password'));
          await updateUser({ password });
          form.reset();
          notice(feedback, 'Password updated.', 'success');
          break;
        }
        default:
          await login(email, password);
          window.location.assign(form.dataset.staff === 'true' ? '/admin/dashboard.php' : '/dashboard.php');
      }
    } catch (error) {
      const text = form.dataset.auth === 'recover' && error.status === 404
        ? 'If an account exists for that address, check your inbox for a recovery link.'
        : form.dataset.auth === 'login' && [400, 401, 422].includes(error.status)
          ? 'Sign-in failed. Check your email and password and make sure your email is confirmed.'
          : 'Unable to complete that request. Check your information and try again.';
      notice(feedback, text, form.dataset.auth === 'recover' && error.status === 404 ? 'success' : 'danger');
    }
  });
}

for (const button of document.querySelectorAll('[data-logout]')) {
  button.addEventListener('click', async () => {
    button.disabled = true;
    try {
      await logout();
      window.location.assign('/');
    } catch {
      button.disabled = false;
      button.textContent = 'Retry sign-out';
    }
  });
}

if (window.location.hash) await handleCallback();
else await getUser();
