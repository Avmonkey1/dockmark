const fields = {
  dockmarkUrl: document.querySelector('#dockmarkUrl'),
  page: document.querySelector('#page'),
  group: document.querySelector('#group'),
  tags: document.querySelector('#tags')
};
const status = document.querySelector('#status');
const save = document.querySelector('#save');

chrome.storage.sync.get(['dockmarkUrl', 'page', 'group', 'tags'], (stored) => {
  Object.entries(fields).forEach(([key, field]) => {
    if (stored[key]) {
      field.value = stored[key];
    }
  });
});

Object.entries(fields).forEach(([key, field]) => {
  field.addEventListener('input', () => chrome.storage.sync.set({ [key]: field.value.trim() }));
});

save.addEventListener('click', async () => {
  status.textContent = 'Saving...';
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  const dockmarkUrl = fields.dockmarkUrl.value.trim().replace(/\/+$/, '');

  if (!dockmarkUrl) {
    status.textContent = 'Set your Dockmark URL first.';
    return;
  }

  try {
    const response = await fetch(`${dockmarkUrl}/api.php`, {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'add_link',
        title: tab.title || tab.url,
        url: tab.url,
        page: fields.page.value.trim() || 'Home',
        group: fields.group.value.trim() || 'Inbox',
        note: 'Saved from Dockmark Clipper',
        icon: 'link',
        tags: fields.tags.value.trim()
      })
    });

    const result = await response.json();
    if (!result.ok) {
      throw new Error(result.message || 'Could not save tab.');
    }

    status.textContent = 'Saved to Dockmark.';
  } catch (error) {
    status.textContent = error.message || 'Save failed.';
  }
});
