/**
 * Question Builder — dynamic survey question management
 */

const typeLabels = {
    true_false: 'True / False',
    multiple_choice: 'Multiple Choice',
    ranking: 'Ranking',
    rating: 'Rating',
    open_ended: 'Open Ended',
};

let questionIndex = 0;
let draggedElement = null;

function addQuestion(type, data = {}) {
    const container = document.getElementById('questions-container');
    const idx = questionIndex++;

    const card = document.createElement('div');
    card.className = 'card mb-4 relative draggable-question';
    card.id = 'question-card-' + idx;
    card.dataset.idx = idx;
    card.draggable = true;

    let html = `
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
                <span class="drag-handle text-gray-400 hover:text-gray-600 cursor-grab active:cursor-grabbing text-xl" title="Drag to reorder">⋮⋮</span>
                <span class="question-number font-bold text-gray-700 bg-gray-100 px-2 py-1 rounded min-w-fit">Q1</span>
                <span class="text-xs font-medium text-primary-500 bg-primary-50 px-2 py-1 rounded">${typeLabels[type]}</span>
            </div>
            <div class="flex items-center gap-1">
                <button type="button" onclick="removeQuestion(${idx})" class="text-red-400 hover:text-red-600 p-1 text-xs ml-2" title="Remove">✕</button>
            </div>
        </div>
        <input type="hidden" name="questions[${idx}][type]" value="${type}">
        <div class="mb-3">
            <input type="text" name="questions[${idx}][title]" value="${escapeHtml(data.title || '')}" class="input-field w-full" placeholder="Question text" required>
        </div>
        <div class="mb-3">
            <input type="text" name="questions[${idx}][description]" value="${escapeHtml(data.description || '')}" class="input-field w-full text-sm" placeholder="Help text (optional)">
        </div>
        <div class="flex items-center gap-4 mb-3">
            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                <input type="checkbox" name="questions[${idx}][is_required]" value="1" ${data.is_required !== false ? 'checked' : ''} class="rounded text-primary-500 focus:ring-primary-300">
                Required
            </label>
        </div>
    `;

    // Type-specific fields
    if (type === 'multiple_choice' || type === 'ranking') {
        const allowMultiple = data.settings?.allow_multiple ?? false;
        html += `<div id="options-container-${idx}" class="space-y-2 mb-3">`;

        const options = data.options && data.options.length > 0 ? data.options : ['', ''];
        options.forEach((opt, optIdx) => {
            html += optionHtml(idx, optIdx, opt);
        });

        html += `</div>`;
        html += `<button type="button" onclick="addOption(${idx})" class="text-sm text-primary-500 hover:text-primary-600">+ Add option</button>`;

        if (type === 'multiple_choice') {
            html += `
                <div class="mt-3">
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" name="questions[${idx}][settings][allow_multiple]" value="1" ${allowMultiple ? 'checked' : ''} class="rounded text-primary-500 focus:ring-primary-300">
                        Allow multiple selections
                    </label>
                </div>
            `;
        }
    }

    if (type === 'rating') {
        const maxRating = data.settings?.max_rating ?? 5;
        html += `
            <div class="flex items-center gap-3">
                <label class="text-sm text-gray-600">Scale: 1 to</label>
                <select name="questions[${idx}][settings][max_rating]" class="input-field text-sm w-20">
                    ${[3, 4, 5, 6, 7, 8, 9, 10].map(n => `<option value="${n}" ${n == maxRating ? 'selected' : ''}>${n}</option>`).join('')}
                </select>
            </div>
        `;
    }

    card.innerHTML = html;
    container.appendChild(card);
    
    // Attach drag events directly to the card
    card.addEventListener('dragstart', handleDragStart);
    card.addEventListener('dragend', handleDragEnd);
    
    reindexQuestions();
}

function optionHtml(qIdx, optIdx, value = '') {
    return `
        <div class="flex items-center gap-2" id="option-${qIdx}-${optIdx}">
            <span class="text-xs text-gray-400 w-4">${optIdx + 1}.</span>
            <input type="text" name="questions[${qIdx}][options][]" value="${escapeHtml(value)}" class="input-field flex-1 text-sm" placeholder="Option text" required>
            <button type="button" onclick="removeOption(${qIdx}, this)" class="text-red-400 hover:text-red-600 text-xs p-1">✕</button>
        </div>
    `;
}

function addOption(qIdx) {
    const container = document.getElementById('options-container-' + qIdx);
    if (!container) return;

    const optCount = container.children.length;
    const div = document.createElement('div');
    div.innerHTML = optionHtml(qIdx, optCount);
    container.appendChild(div.firstElementChild);
}

function removeOption(qIdx, btn) {
    const container = document.getElementById('options-container-' + qIdx);
    if (container.children.length <= 2) {
        alert('You need at least 2 options.');
        return;
    }
    btn.closest('.flex').remove();
}

function removeQuestion(idx) {
    const card = document.getElementById('question-card-' + idx);
    if (card) card.remove();
    reindexQuestions();
}

function reindexQuestions() {
    // Update question numbers and reorder form fields based on DOM order
    const container = document.getElementById('questions-container');
    const cards = Array.from(container.children);

    cards.forEach((card, position) => {
        const questionNum = position + 1;
        
        // Update visual number
        const numberSpan = card.querySelector('.question-number');
        if (numberSpan) {
            numberSpan.textContent = `Q${questionNum}`;
        }

        // Update ALL form field names to use the new position index
        const allInputs = card.querySelectorAll('input, textarea, select');
        allInputs.forEach(input => {
            const oldName = input.getAttribute('name');
            if (oldName && oldName.includes('questions[')) {
                // Replace all questions[OLD_IDX] with questions[NEW_IDX]
                const newName = oldName.replace(/questions\[\d+\]/, `questions[${position}]`);
                input.setAttribute('name', newName);
                
                // Also update onclick handlers for option removal
                if (input.nextElementSibling && input.nextElementSibling.onclick) {
                    const onclickText = input.nextElementSibling.getAttribute('onclick');
                    if (onclickText && onclickText.includes('removeOption')) {
                        // Update the removeOption call with new index
                        const newOnclick = onclickText.replace(/removeOption\(\d+/, `removeOption(${position}`);
                        input.nextElementSibling.setAttribute('onclick', newOnclick);
                    }
                }
            }
        });

        // Update onclick handlers for addOption button
        const addOptionBtn = card.querySelector('button[onclick*="addOption"]');
        if (addOptionBtn) {
            const newOnclick = `addOption(${position})`;
            addOptionBtn.setAttribute('onclick', newOnclick);
        }

        // Update onclick handler for removeQuestion button
        const removeBtn = card.querySelector('button[onclick*="removeQuestion"]');
        if (removeBtn) {
            const newOnclick = `removeQuestion(${position})`;
            removeBtn.setAttribute('onclick', newOnclick);
        }

        // Update the options container ID
        const oldContainerId = `options-container-${card.dataset.idx}`;
        const newContainerId = `options-container-${position}`;
        const optionsContainer = card.querySelector(`#${oldContainerId}`);
        if (optionsContainer) {
            optionsContainer.id = newContainerId;
        }

        // Update option remove buttons' onclick handlers
        const optionRemoveBtns = card.querySelectorAll('button[onclick*="removeOption"]');
        optionRemoveBtns.forEach(btn => {
            const oldOnclick = btn.getAttribute('onclick');
            if (oldOnclick) {
                const newOnclick = oldOnclick.replace(/removeOption\(\d+/, `removeOption(${position}`);
                btn.setAttribute('onclick', newOnclick);
            }
        });
    });
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Bulk Add Questions Functions
function openBulkAddModal() {
    document.getElementById('bulk-add-modal').style.display = 'flex';
}

function closeBulkAddModal() {
    document.getElementById('bulk-add-modal').style.display = 'none';
}

function addMultipleQuestions() {
    const questionType = document.getElementById('bulk-question-type').value;
    const questionCount = parseInt(document.getElementById('bulk-question-count').value);

    // Validate input
    if (isNaN(questionCount) || questionCount < 1 || questionCount > 50) {
        alert('Please enter a number between 1 and 50');
        return;
    }

    // Add the specified number of questions
    for (let i = 0; i < questionCount; i++) {
        addQuestion(questionType);
    }

    closeBulkAddModal();
}

// Close modal when clicking outside of it
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('bulk-add-modal');
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeBulkAddModal();
            }
        });
    }
});

// Load existing questions on page load
document.addEventListener('DOMContentLoaded', function () {
    if (window.existingQuestions && window.existingQuestions.length > 0) {
        window.existingQuestions.forEach(function (q) {
            addQuestion(q.type, q);
        });
    }
    
    // Initialize drag-and-drop event listeners on container
    initializeDragAndDrop();
});

// Drag and Drop Implementation
function initializeDragAndDrop() {
    const container = document.getElementById('questions-container');
    if (!container) return;

    container.addEventListener('dragover', handleDragOver, false);
    container.addEventListener('drop', handleDrop, false);
    container.addEventListener('dragenter', handleDragEnter, false);
}

function handleDragStart(e) {
    const card = e.target.closest('.draggable-question');
    if (card) {
        draggedElement = card;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', '');
        setTimeout(() => {
            card.style.opacity = '0.6';
            card.style.backgroundColor = '#f3f4f6';
        }, 0);
    }
}

function handleDragEnter(e) {
    e.preventDefault();
}

function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    e.dataTransfer.dropEffect = 'move';

    if (!draggedElement) return;

    const container = document.getElementById('questions-container');
    const cards = Array.from(container.querySelectorAll('.draggable-question'));
    const afterElement = getDragAfterElement(container, e.clientY);

    if (afterElement == null) {
        container.appendChild(draggedElement);
    } else {
        container.insertBefore(draggedElement, afterElement);
    }
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    
    if (draggedElement) {
        draggedElement.style.opacity = '1';
        draggedElement.style.backgroundColor = '';
        reindexQuestions();
    }
}

function handleDragEnd(e) {
    if (draggedElement) {
        draggedElement.style.opacity = '1';
        draggedElement.style.backgroundColor = '';
        draggedElement = null;
    }
}

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.draggable-question:not([style*="opacity: 0.6"])')];

    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;

        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

// Make functions globally available
window.addQuestion = addQuestion;
window.addOption = addOption;
window.removeOption = removeOption;
window.removeQuestion = removeQuestion;
window.openBulkAddModal = openBulkAddModal;
window.closeBulkAddModal = closeBulkAddModal;
window.addMultipleQuestions = addMultipleQuestions;
window.initializeDragAndDrop = initializeDragAndDrop;
