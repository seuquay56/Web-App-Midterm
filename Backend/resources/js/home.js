import './bootstrap';
import {
    cacheServerNotes,
    localNoteId,
    newLocalNoteId,
    queueOfflineImages,
    registerOfflineWorker,
    removeOfflineNote,
    saveOfflineNote,
    syncOfflineNotes
} from './offline-notes';

$(function () {
    const $root = $('html');
    const $results = $('#notesResults');
    const $search = $('#noteSearch');
    const $empty = $('#emptyState');
    const viewKey = 'noteapp-homecopy-view';
    const themeKey = 'noteapp-homecopy-theme';
    const draftKey = 'noteapp-homecopy-draft';
    let activeFilter = 'all';
    let activeLabel = '';
    let searchResultIds = null;
    let searchRequestId = 0;
    let cardToDelete = null;
    let searchTimer;
    let saveTimer;
    let justSavedNote = false;
    let editingNote = null;
    let autosaveTimer;
    let pendingImageFiles = [];
    let pendingImageUrls = [];
    let imageViewerTrigger = null;
    let lockCard = null;
    let lockMode = '';
    let pendingLockedAction = null;
    const offlineUserId = $('meta[name="offline-user-id"]').attr('content');
    let offlineSyncInProgress = false;

    function noteUrl(templateName, noteId, imageId) {
        let url = $('meta[name="' + templateName + '"]').attr('content')
            .replace('__NOTE_ID__', encodeURIComponent(noteId));
        if (imageId !== undefined) {
            url = url.replace('__IMAGE_ID__', encodeURIComponent(imageId));
        }
        return url;
    }

    function showOfflineStatus(message) {
        $('#offlineStatus').text(message);
    }

    async function cacheRemoteNotes() {
        const response = await axios.get($('meta[name="note-offline-url"]').attr('content'));
        await cacheServerNotes(offlineUserId, response.data.notes || []);
    }

    async function syncOfflineQueue() {
        if (!navigator.onLine || offlineSyncInProgress || !offlineUserId) {
            return;
        }
        offlineSyncInProgress = true;
        try {
            const result = await syncOfflineNotes(offlineUserId);
            if (result.errors.length > 0) {
                showOfflineStatus('Some offline changes could not sync. They remain saved on this device.');
            } else if (result.syncedNotes || result.syncedImages) {
                showOfflineStatus('Offline changes synced.');
                if (!document.getElementById('editorModal').classList.contains('show')) {
                    window.location.reload();
                    return;
                }
            }
            await cacheRemoteNotes();
        } catch (error) {
            if (!navigator.onLine || error instanceof TypeError) {
                showOfflineStatus('Offline — open Offline Notes to view and edit saved notes.');
            } else {
                console.error('Unable to prepare offline notes.', error);
                showOfflineStatus('Offline cache could not be refreshed.');
            }
        } finally {
            offlineSyncInProgress = false;
        }
    }

    function initializeOfflineSupport() {
        try {
            window.localStorage.setItem('noteapp-offline-user-id', String(offlineUserId));
        } catch (error) {
            console.warn('Unable to remember the offline account on this device.', error);
        }
        registerOfflineWorker().catch(function (error) {
            console.error('Unable to register the offline service worker.', error);
            showOfflineStatus('Offline mode is unavailable in this browser.');
        });
        if (navigator.onLine) {
            syncOfflineQueue();
            showOfflineStatus('Offline notes are saved on this device when available.');
        } else {
            showOfflineStatus('Offline — open Offline Notes to view and edit saved notes.');
        }
    }

    function queueEditorChangesOffline(payload) {
        if (editingNote?.isProtected) {
            return Promise.reject(new Error('Password-protected notes cannot be saved offline.'));
        }
        const serverId = editingNote?.serverId || editingNote?.id || null;
        const localId = editingNote?.localId || (serverId ? localNoteId(serverId) : newLocalNoteId());
        return saveOfflineNote(offlineUserId, localId, {
            serverId: serverId ? String(serverId) : null,
            title: payload.title,
            content: payload.content
        }).then(function () {
            return queueOfflineImages(offlineUserId, localId, pendingImageFiles);
        }).then(function () {
            editingNote = null;
            clearPendingImages();
            justSavedNote = true;
            window.localStorage.removeItem(draftKey);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editorModal')).hide();
            showOfflineStatus('Saved on this device. Open Offline Notes to view it; sync will resume when connected.');
        });
    }

    window.addEventListener('online', syncOfflineQueue);
    window.addEventListener('offline', function () {
        showOfflineStatus('Offline — open Offline Notes to view and edit saved notes.');
    });

    function clearPendingImages() {
        pendingImageUrls.forEach(function (url) {
            URL.revokeObjectURL(url);
        });
        pendingImageUrls = [];
        pendingImageFiles = [];
        $('#noteImages').val('');
        $('#noteImagePreview').empty();
        updateImagesFrameVisibility();
    }

    function updateImagesFrameVisibility() {
        const hasImages = $('#existingNoteImages .note-image-item, #noteImagePreview .note-image-item').length > 0;
        $('#noteImagesFrame').toggleClass('d-none', !hasImages);
    }

    function setTheme(theme) {
        $root.attr('data-bs-theme', theme);
        const isLight = theme === 'light';
        $('#themeToggle')
            .attr('aria-label', isLight ? 'Change to dark theme' : 'Change to light theme')
            .find('.moon-icon').toggleClass('d-none', !isLight);
        
        $('#themeToggle')
            .find('.sun-icon').toggleClass('d-none', isLight);
        
        $('meta[name="theme-color"]').attr('content', isLight ? '#f5f6f2' : '#080a0e');
        try {
            localStorage.setItem(themeKey, theme);
        } catch (error) {
            console.warn('Unable to save the selected theme.', error);
        }
    }

    function setView(view) {
        const selectedView = view === 'list' ? 'list' : 'grid';
        $results.attr('data-view', selectedView);
        $('.view-option').each(function () {
            const selected = $(this).data('view') === selectedView;
            $(this).toggleClass('is-active', selected).attr('aria-pressed', selected ? 'true' : 'false');
        });
        try {
            localStorage.setItem(viewKey, selectedView);
        } catch (error) {
            console.warn('Unable to save the selected view.', error);
        }
    }

    function updateEmptyState() {
        const isEmpty = $results.find('.note-card[data-filter-visible="true"]').length === 0;
        $empty.toggleClass('d-none', !isEmpty);
        $results.toggleClass('d-none', isEmpty);
        if (isEmpty) {
            const hasSearch = String($search.val() || '').trim().length > 0;
            $('#emptyTitle').text(hasSearch ? 'No notes found' : "You don't have any notes yet");
            $

            $('#emptyDescription').text(hasSearch
                ? 'Try another keyword or choose a label to narrow your search.'
                : 'Write your first note to capture an idea.');
        }
    }

    function filterNotes() {
        const query = String($search.val() || '').trim().toLocaleLowerCase('en');
        $results.find('.note-card').each(function () {
            const $card = $(this);
            const labels = String($card.data('labels') || '').split('|');
            const matchesSearch = searchResultIds === null || searchResultIds.has(String($card.data('note-id')));
            const matchesFilter = activeFilter !== 'shared' || $card.data('shared') === true || $card.attr('data-shared') === 'true';
            const matchesLabel = !activeLabel || labels.includes(activeLabel);
            const matches = matchesSearch && matchesFilter && matchesLabel;
            $card.attr('data-filter-visible', matches ? 'true' : 'false').toggle(matches);
        });
        $results.find('.note-group').each(function () {
            $(this).toggle($(this).find('.note-card[data-filter-visible="true"]').length > 0);
        });
        updateEmptyState();
    }

    function setFilter(filter, label) {
        activeFilter = filter || 'all';
        activeLabel = label || '';
        $('.sidebar-link[data-filter], .sidebar-link[data-filter-label]').removeClass('is-selected');
        if (activeLabel) {
            $('.sidebar-link[data-filter-label]').filter(function () {
                return $(this).attr('data-filter-label') === activeLabel;
            }).addClass('is-selected');
            $('#activeFilterLabel').text(activeLabel);
        } else {
            $('.sidebar-link[data-filter="' + activeFilter + '"]').addClass('is-selected');
            $('#activeFilterLabel').text(activeFilter === 'shared' ? 'shared with me' : 'recent');
        }
        filterNotes();
    }

    function moveCardToGroup($card) {
        const isPinned = $card.attr('data-pinned') === 'true';
        const target = isPinned ? 'pinned' : 'other';
        $card.appendTo($results.find('.note-group[data-group="' + target + '"] .note-grid'));
        $results.find('.note-group').each(function () {
            $(this).toggle($(this).find('.note-card').length > 0);
        });
        filterNotes();
    }

    $('.view-option').on('click', function () {
        setView($(this).data('view'));
    });

    $('#themeToggle').on('click', function () {
        setTheme($root.attr('data-bs-theme') === 'light' ? 'dark' : 'light');
    });

    $('.sidebar-link[data-filter]').on('click', function () {
        setFilter($(this).attr('data-filter'), '');
    });

    $('.label-filter').on('click', function () {
        setFilter('all', $(this).attr('data-filter-label'));
    });

    $search.on('input', function () {
        window.clearTimeout(searchTimer);
        const query = String($search.val() || '').trim();
        const requestId = ++searchRequestId;
        searchTimer = window.setTimeout(function () {
            axios.get($('meta[name="notes-index-url"]').attr('content'), {
                params: { search: query }
            })
                .then(function (response) {
                    if (requestId !== searchRequestId) {
                        return;
                    }

                    searchResultIds = new Set(
                        response.data.notes.map(function (note) {
                            return String(note.id);
                        })
                    );
                    filterNotes();
                })
                .catch(function (error) {
                    if (requestId !== searchRequestId) {
                        return;
                    }

                    console.error('Unable to search notes.', error);
                    window.alert(error.response?.data?.message || 'Unable to search notes. Please try again.');
                });
        }, 300);
    });

    $(document).on('keydown', function (event) {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            $search.trigger('focus');
        }
    });

    $('#verificationAlert .alert-close').on('click', function () {
        $('#verificationAlert').addClass('d-none');
    });

    $('.pin-note').on('click', function () {
        const $card = $(this).closest('.note-card');
        const isPinned = $card.attr('data-pinned') === 'true';
        axios.post($card.data('pin-url'))
            .then(function (response) {
                const pinned = response.data.pinned;
                $card.attr('data-pinned', pinned ? 'true' : 'false');
                $card.find('.pin-note').attr('aria-label', pinned ? 'Unpin note' : 'Pin note');
                $card.find('.pin-note .bi').toggleClass('bi-pin-angle', !pinned).toggleClass('bi-pin-angle-fill', pinned);
                $card.find('.note-meta-icons .status-pinned').remove();
                if (pinned) {
                    $card.find('.note-meta-icons').prepend('<span class="status-icon status-pinned" title="Pinned" aria-label="Pinned"><i class="bi bi-pin-angle-fill"></i></span>');
                }
                moveCardToGroup($card);
            })
            .catch(function (error) {
                console.error('Unable to update the pinned status.', error);
                window.alert(error.response?.data?.message || 'Unable to pin the note.');
            });
    });

    function openLockModal($card, mode) {
        lockCard = $card;
        lockMode = mode;
        $('#noteLockError').addClass('d-none').text('');
        $('#oldNotePassword, #notePassword, #newNotePassword, #confirmNotePassword').val('');

        const hasPassword = $card.attr('data-has-password') === 'true';
        $('#oldPasswordField').toggleClass('d-none', mode !== 'change');
        $('#currentPasswordField').toggleClass('d-none', mode !== 'unlock' && mode !== 'disable');
        $('#newPasswordFields').toggleClass('d-none', mode === 'unlock' || mode === 'disable');
        $('#disableNoteLock').toggleClass('d-none', mode !== 'manage');
        $('#changeNotePassword').toggleClass('d-none', mode !== 'manage');

        if (mode === 'unlock') {
            $('#noteLockTitle').text('Unlock note');
            $('#noteLockDescription').text('Enter the note password to view and edit this note.');
            $('#submitNoteLock').text('Unlock');
        } else if (mode === 'disable') {
            $('#noteLockTitle').text('Remove password');
            $('#noteLockDescription').text('Enter the current password to remove protection.');
            $('#submitNoteLock').text('Remove password');
        } else if (mode === 'change') {
            $('#noteLockTitle').text('Change note password');
            $('#noteLockDescription').text('Verify your current password and enter a new one.');
            $('#submitNoteLock').text('Change password');
        } else if (mode === 'manage') {
            $('#noteLockTitle').text('Note password');
            $('#noteLockDescription').text('This note is protected by a password.');
            $('#submitNoteLock').addClass('d-none');
        } else {
            $('#noteLockTitle').text('Protect note');
            $('#noteLockDescription').text('Enter a new password and confirm it.');
            $('#submitNoteLock').text('Protect note');
        }

        if (mode === 'enable') {
            $('#submitNoteLock').removeClass('d-none');
        } else if (mode !== 'manage') {
            $('#submitNoteLock').removeClass('d-none');
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('noteLockModal')).show();
    }

    function requireUnlocked($card, action) {
        if ($card.attr('data-has-password') !== 'true' || $card.attr('data-unlocked') === 'true') {
            action();
            return;
        }
        pendingLockedAction = action;
        openLockModal($card, 'unlock');
    }

    $('.lock-note').on('click', function (event) {
        event.stopPropagation();
        const $card = $(this).closest('.note-card');
        openLockModal($card, $card.attr('data-has-password') === 'true' ? 'manage' : 'enable');
    });

    $('#changeNotePassword').on('click', function () {
        openLockModal(lockCard, 'change');
    });

    $('#disableNoteLock').on('click', function () {
        openLockModal(lockCard, 'disable');
    });

    $('.delete-note').on('click', function (event) {
        event.stopPropagation();
        const $card = $(this).closest('.note-card');
        requireUnlocked($card, function () {
            cardToDelete = $card;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteModal')).show();
        });
    });

    $('#editorPinAction').on('click', function () {
        if (!editingNote?.card) return;
        const $card = editingNote.card;
        axios.post($card.data('pin-url'))
            .then(function (response) {
                const pinned = response.data.pinned;
                $card.attr('data-pinned', pinned ? 'true' : 'false');
                $card.find('.pin-note').attr('aria-label', pinned ? 'Unpin note' : 'Pin note');
                $card.find('.pin-note .bi')
                    .toggleClass('bi-pin-angle', !pinned)
                    .toggleClass('bi-pin-angle-fill', pinned);
                $card.find('.note-meta-icons .status-pinned').remove();
                if (pinned) {
                    $card.find('.note-meta-icons').prepend(
                        '<span class="status-icon status-pinned" title="Pinned" aria-label="Pinned"><i class="bi bi-pin-angle-fill"></i></span>'
                    );
                }
                $('#editorPinActionText').text(pinned ? 'Unpin note' : 'Pin note');
                $('#editorPinAction i')
                    .toggleClass('bi-pin-angle', !pinned)
                    .toggleClass('bi-pin-angle-fill', pinned);
                moveCardToGroup($card);
            })
            .catch(function (error) {
                window.alert(error.response?.data?.message || 'Unable to pin the note.');
            });
    });

    $('#editorLockAction').on('click', function () {
        if (!editingNote?.card) return;
        const $card = editingNote.card;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editorModal')).hide();
        window.setTimeout(function () {
            openLockModal(
                $card,
                $card.attr('data-has-password') === 'true' ? 'disable' : 'enable'
            );
        }, 150);
    });

    $('#editorDeleteAction').on('click', function () {
        if (!editingNote?.card) return;
        cardToDelete = editingNote.card;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editorModal')).hide();
        window.setTimeout(function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteModal')).show();
        }, 150);
    });

    function openEditModal($card) {
        editingNote = {
            id: $card.data('note-id'),
            card: $card,
            updateUrl: $card.data('update-url'),
            isProtected: $card.attr('data-has-password') === 'true'
        };
        if (editingNote.isProtected) {
            window.localStorage.removeItem(draftKey);
        }
        $('#editorActionsMenu').removeClass('d-none');
        const isPinned = $card.attr('data-pinned') === 'true';
        $('#editorPinActionText').text(isPinned ? 'Unpin note' : 'Pin note');
        $('#editorPinAction i')
            .toggleClass('bi-pin-angle', !isPinned)
            .toggleClass('bi-pin-angle-fill', isPinned);
        const hasPassword = $card.attr('data-has-password') === 'true';
        $('#editorLockActionText').text(hasPassword ? 'Unlock note' : 'Protect note');
        $('#editorLockActionIcon')
            .toggleClass('bi-lock', !hasPassword)
            .toggleClass('bi-unlock', hasPassword);
        $('#editorModalTitle').text('Edit Note');
        $('#editorNoteTitle').val($card.data('title') || '');
        $('#editorNoteContent').val($card.data('content') || '');
        $('#saveNote').text('Save');
        $('#autosaveStatus').text('Editing note');
        clearPendingImages();
        loadExistingImages($card.data('note-id'));
        window.clearTimeout(saveTimer);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editorModal')).show();
    }

    function loadExistingImages(noteId) {
        $('#existingNoteImages').empty();
        updateImagesFrameVisibility();
        axios.get(noteUrl('note-show-url-template', noteId))
            .then(function (response) {
                (response.data.note.images || []).forEach(function (image) {
                    const $item = $('<div>', {
                        class: 'note-image-item',
                        'data-image-id': image.id
                    });
                    $('<img>', {
                        src: image.url,
                        alt: 'Attached note image',
                        class: 'image-preview-trigger',
                        tabindex: 0,
                        role: 'button',
                        'aria-label': 'Open image at full size'
                    }).appendTo($item);
                    $('<button>', {
                        type: 'button',
                        class: 'note-image-remove remove-existing-note-image',
                        text: '×',
                        'aria-label': 'Delete image'
                    }).appendTo($item);
                    $item.appendTo('#existingNoteImages');
                });
                updateImagesFrameVisibility();
            })
            .catch(function (error) {
                console.error('Unable to load note images.', error);
                $('#autosaveStatus').text('Unable to load attached images.');
            });
    }

    $('#noteImages').on('change', function () {
        const files = Array.from(this.files || []);
        const invalidFile = files.find(function (file) {
            return !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024;
        });
        if (invalidFile) {
            window.alert('Only JPG, PNG, or WebP images up to 5 MB each are allowed.');
            this.value = '';
            return;
        }
        if (pendingImageFiles.length + files.length > 10) {
            window.alert('Each note can have up to 10 images.');
            this.value = '';
            return;
        }

        pendingImageFiles = pendingImageFiles.concat(files);
        this.value = '';
        renderPendingImages();
    });

    function renderPendingImages() {
        pendingImageUrls.forEach(function (url) {
            URL.revokeObjectURL(url);
        });
        pendingImageUrls = [];
        const $preview = $('#noteImagePreview').empty();
        pendingImageFiles.forEach(function (file, index) {
            const imageUrl = URL.createObjectURL(file);
            pendingImageUrls.push(imageUrl);
            const $item = $('<div>', {
                class: 'note-image-item',
                'data-file-index': index
            });
            $('<img>', {
                src: imageUrl,
                alt: 'Image preview',
                class: 'image-preview-trigger',
                tabindex: 0,
                role: 'button',
                'aria-label': 'Open image at full size'
            }).appendTo($item);
            $('<button>', {
                type: 'button',
                class: 'note-image-remove remove-pending-note-image',
                text: '×',
                'aria-label': 'Remove this image'
            }).appendTo($item);
            $preview.append($item);
        });
        updateImagesFrameVisibility();
    }

    $('#noteImagePreview').on('click', '.remove-pending-note-image', function () {
        const index = Number($(this).closest('.note-image-item').data('file-index'));
        pendingImageFiles.splice(index, 1);
        renderPendingImages();
    });

    $('#existingNoteImages').on('click', '.remove-existing-note-image', function () {
        const $item = $(this).closest('.note-image-item');
        const url = noteUrl(
            'note-image-delete-url-template',
            editingNote.id,
            $item.data('image-id')
        );
        $(this).prop('disabled', true);
        axios.delete(url)
            .then(function () {
                $item.remove();
                updateImagesFrameVisibility();
            })
            .catch(function (error) {
                console.error('Unable to delete note image.', error);
                window.alert(error.response?.data?.message || 'Unable to delete the image.');
                $item.find('.note-image-remove').prop('disabled', false);
            });
    });

    function openImageViewer(imageUrl, trigger) {
        imageViewerTrigger = trigger;
        $('#imageViewerContent').attr('src', imageUrl);
        $('#imageViewer').removeClass('d-none');
        $('#closeImageViewer').trigger('focus');
    }

    function closeImageViewer() {
        $('#imageViewer').addClass('d-none');
        $('#imageViewerContent').attr('src', '');
        if (imageViewerTrigger) {
            imageViewerTrigger.focus();
            imageViewerTrigger = null;
        }
    }

    $('#editorModal').on('click', '.image-preview-trigger', function () {
        openImageViewer(this.src, this);
    });

    $('#editorModal').on('keydown', '.image-preview-trigger', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openImageViewer(this.src, this);
        }
    });

    $('#closeImageViewer').on('click', closeImageViewer);
    $('#imageViewer').on('click', function (event) {
        if (event.target === this) {
            closeImageViewer();
        }
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape' && !$('#imageViewer').hasClass('d-none')) {
            closeImageViewer();
        }
    });

    function uploadNoteImages(noteId) {
        const files = pendingImageFiles;
        if (!files || files.length === 0) {
            return Promise.resolve();
        }

        const formData = new FormData();
        Array.from(files).forEach(function (file) {
            formData.append('images[]', file);
        });

        return axios.post(noteUrl('note-images-store-url-template', noteId), formData);
    }

    $('.edit-note').on('click', function (event) {
        event.stopPropagation();
        const $card = $(this).closest('.note-card');
        requireUnlocked($card, function () {
            openEditModal($card);
        });
    });

    $('.note-hover-actions').on('click', function (event) {
        event.stopPropagation();
    });

    $('.note-card').on('click', function () {
        const $card = $(this);
        requireUnlocked($card, function () {
            openEditModal($card);
        });
    });

    $('#noteLockForm').on('submit', function (event) {
        event.preventDefault();
        if (!lockCard) return;

        const noteId = lockCard.data('note-id');
        let url;
        let method;
        let payload = {};

        if (lockMode === 'unlock') {
            url = noteUrl('note-unlock-check-url-template', noteId);
            method = axios.post;
            payload.password = $('#notePassword').val();
        } else if (lockMode === 'enable') {
            url = noteUrl('note-lock-url-template', noteId);
            method = axios.post;
            payload.password = $('#newNotePassword').val();
            payload.password_confirmation = $('#confirmNotePassword').val();
        } else if (lockMode === 'change') {
            url = noteUrl('note-lock-change-url-template', noteId);
            method = axios.put;
            payload.old_password = $('#oldNotePassword').val();
            payload.password = $('#newNotePassword').val();
            payload.password_confirmation = $('#confirmNotePassword').val();
        } else if (lockMode === 'disable') {
            url = noteUrl('note-lock-remove-url-template', noteId);
            method = axios.delete;
            payload.password = $('#notePassword').val();
        } else {
            return;
        }

        $('#submitNoteLock').prop('disabled', true);
        const request = lockMode === 'disable'
            ? axios.delete(url, { data: payload })
            : method(url, payload);

        request
            .then(function (response) {
                removeOfflineNote(offlineUserId, localNoteId(noteId))
                    .catch(function (error) {
                        console.error('Unable to remove a protected note from the offline cache.', error);
                    })
                    .then(function () {
                        if (lockMode === 'unlock') {
                            lockCard.attr('data-unlocked', 'true');
                            lockCard.attr('data-title', response.data.note.title || '');
                            lockCard.attr('data-content', response.data.note.content || '');
                            lockCard.attr('data-search', (response.data.note.title || '') + ' ' + (response.data.note.content || ''));
                            lockCard.find('.note-title').text(response.data.note.title || 'hello');
                            lockCard.find('.note-excerpt').text(response.data.note.content || '');
                            bootstrap.Modal.getOrCreateInstance(document.getElementById('noteLockModal')).hide();
                            if (pendingLockedAction) {
                                const action = pendingLockedAction;
                                pendingLockedAction = null;
                                action();
                            }
                        } else {
                            window.location.reload();
                        }
                    });
            })
            .catch(function (error) {
                const errors = error.response?.data?.errors;
                const message = errors
                    ? Object.values(errors)[0][0]
                    : (error.response?.data?.message || 'Unable to update note password settings.');
                $('#noteLockError').text(message).removeClass('d-none');
            })
            .finally(function () {
                $('#submitNoteLock').prop('disabled', false);
            });
    });

    $('#noteLockModal').on('hidden.bs.modal', function () {
        if (lockMode === 'unlock') {
            pendingLockedAction = null;
        }
        $('#noteLockError').addClass('d-none').text('');
    });

    $('#confirmDelete').on('click', function () {
        if (cardToDelete) {
            const deleteUrl = cardToDelete.data('delete-url');
            axios.delete(deleteUrl)
                .then(function () {
                    cardToDelete = null;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteModal')).hide();
                    window.location.reload();
                })
                .catch(function (error) {
                    console.error('Unable to delete note.', error);
                    window.alert(error.response?.data?.message || 'Unable to delete the note.');
                });
        }
    });

    $('#saveNote').on('click', function () {
        const $button = $(this);
        $button.prop('disabled', true).text('Saving...');
        window.clearTimeout(autosaveTimer);

        const payload = {
            title: $('#editorNoteTitle').val(),
            content: $('#editorNoteContent').val()
        };
        const request = editingNote
            ? axios.put(editingNote.updateUrl, payload)
            : axios.post($('meta[name="note-store-url"]').attr('content'), payload);

        request
            .then(function (response) {
                const noteId = editingNote ? editingNote.id : response.data.note?.id;
                if (!noteId) {
                    throw new Error('The note ID was not returned after saving.');
                }

                if (!editingNote) {
                    editingNote = {
                        id: noteId,
                        card: null,
                        updateUrl: noteUrl('note-update-url-template', noteId)
                    };
                    $('#editorModalTitle').text('Edit Note');
                }

                return uploadNoteImages(noteId).catch(function (error) {
                    if (!error.response && !editingNote?.isProtected) {
                        const localId = localNoteId(noteId);
                        return saveOfflineNote(offlineUserId, localId, {
                            serverId: String(noteId),
                            title: payload.title,
                            content: payload.content
                        }).then(function () {
                            return queueOfflineImages(offlineUserId, localId, pendingImageFiles);
                        });
                    }
                    throw error;
                }).then(function () {
                    justSavedNote = true;
                    window.clearTimeout(saveTimer);
                    pendingImageFiles = [];
                    pendingImageUrls.forEach(function (url) {
                        URL.revokeObjectURL(url);
                    });
                    pendingImageUrls = [];
                    $('#noteImages').val('');
                    $('#editorNoteTitle').val('');
                    $('#editorNoteContent').val('');
                    window.localStorage.removeItem(draftKey);
                    editingNote = null;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('editorModal')).hide();
                    window.location.reload();
                });
            })
            .catch(function (error) {
                if (!error.response && !editingNote?.isProtected) {
                    queueEditorChangesOffline(payload).catch(function (storageError) {
                        console.error('Unable to save the note offline.', storageError);
                        $('#autosaveStatus').text(storageError.message || 'Unable to save the note offline.');
                    });
                    return;
                }
                const message = error.response?.data?.message
                    || (error.response?.data?.errors ? Object.values(error.response.data.errors)[0][0] : null)
                    || 'Unable to save the note.';
                $('#autosaveStatus').text(message);
            })
            .finally(function () {
                $button.prop('disabled', false).text(editingNote ? 'Save' : 'Add');
            });
    });

    $('.create-note-trigger').on('click', function () {
        window.clearTimeout(autosaveTimer);
        editingNote = null;
        $('#editorActionsMenu').addClass('d-none');
        $('#editorModalTitle').text('Add New Note');
        $('#saveNote').text('Add');
        $('#existingNoteImages').empty();
        clearPendingImages();
        const draft = readDraft();
        $('#editorNoteTitle').val(draft.title || '');
        $('#editorNoteContent').val(draft.content || '');
        $('#autosaveStatus').text(draft.title || draft.content ? 'Saved on this device' : 'Draft saved on this device');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editorModal')).show();
    });

    function readDraft() {
        try {
            return JSON.parse(localStorage.getItem(draftKey) || '{}');
        } catch (error) {
            console.warn('Unable to read the saved draft.', error);
            return {};
        }
    }

    function saveDraft() {
        if (editingNote?.isProtected) {
            window.localStorage.removeItem(draftKey);
            return;
        }
        const draft = {
            title: $('#editorNoteTitle').val(),
            content: $('#editorNoteContent').val()
        };
        try {
            localStorage.setItem(draftKey, JSON.stringify(draft));
            $('#autosaveStatus').text('Saved on this device');
        } catch (error) {
            $('#autosaveStatus').text('Unable to save draft');
            console.warn('Unable to save draft.', error);
        }
    }

    function autosaveNote() {
        if (!editingNote) {
            return;
        }

        const payload = {
            title: $('#editorNoteTitle').val(),
            content: $('#editorNoteContent').val(),
        };

        if (!payload.title.trim() && !payload.content.trim()) {
            return;
        }

        $('#autosaveStatus').text('Saving...');

        axios.put(editingNote.updateUrl, payload)
            .then(function () {
                window.localStorage.removeItem(draftKey);
                $('#autosaveStatus').text('Saved');
            })
            .catch(function (error) {
                if (!error.response && !editingNote?.isProtected) {
                    saveOfflineNote(offlineUserId, localNoteId(editingNote.id), {
                        serverId: String(editingNote.id),
                        title: payload.title,
                        content: payload.content
                    }).then(function () {
                        $('#autosaveStatus').text('Saved on this device; waiting to sync.');
                    }).catch(function (storageError) {
                        console.error('Unable to save the note offline.', storageError);
                        $('#autosaveStatus').text(storageError.message || 'Unable to save the note offline.');
                    });
                    return;
                }
                const message = error.response?.data?.message || 'Unable to save the note.';
                $('#autosaveStatus').text(message);
            });
    }

    $('#editorNoteTitle, #editorNoteContent').on('input', function () {
        $('#autosaveStatus').text(editingNote?.isProtected && !navigator.onLine
            ? 'Password-protected notes cannot be saved offline.'
            : 'Saving...');
        window.clearTimeout(saveTimer);
        if (editingNote?.isProtected) {
            window.localStorage.removeItem(draftKey);
        } else {
            saveTimer = window.setTimeout(saveDraft, 400);
        }
        window.clearTimeout(autosaveTimer);
        if (editingNote) {
            autosaveTimer = window.setTimeout(autosaveNote, 1000);
        }
    });

    $('#editorModal').on('hidden.bs.modal', function () {
        window.clearTimeout(saveTimer);
        window.clearTimeout(autosaveTimer);
        $('#editorActionsMenu').addClass('d-none');
        const wasProtected = editingNote?.isProtected === true;
        if (editingNote?.card && editingNote.card.attr('data-has-password') === 'true') {
            axios.post(noteUrl(
                'note-unlock-session-clear-url-template',
                editingNote.card.data('note-id')
            ));
            editingNote.card.attr('data-unlocked', 'false');
            editingNote.card.attr('data-locked', 'true');
        }
        if (wasProtected) {
            window.localStorage.removeItem(draftKey);
        }
        if (justSavedNote) {
            window.localStorage.removeItem(draftKey);
            $('#editorNoteTitle').val('');
            $('#editorNoteContent').val('');
            justSavedNote = false;
            return;
        }
        if (editingNote) {
            editingNote = null;
            $('#editorModalTitle').text('Add New Note');
            $('#saveNote').text('Add');
            $('#editorNoteTitle').val('');
            $('#editorNoteContent').val('');
            window.localStorage.removeItem(draftKey);
            return;
        }
        if (!wasProtected) {
            saveDraft();
        }
    });

    try {
        const savedTheme = localStorage.getItem(themeKey);
        const savedView = localStorage.getItem(viewKey);
        if (savedTheme === 'light' || savedTheme === 'dark') {
            setTheme(savedTheme);
        }
        setView(savedView || 'grid');
    } catch (error) {
        console.warn('Unable to read saved display preferences.', error);
        setView('grid');
    }

    filterNotes();
    initializeOfflineSupport();
});
