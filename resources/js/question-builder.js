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

function addQuestion(type, data = {}) {
    const container = document.getElementById('questions-container');
    const idx = questionIndex++;

    const card = document.createElement('div');
    card.className = 'card mb-4 relative';
    card.id = 'question-card-' + idx;
    card.dataset.idx = idx;

    let html = `
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-primary-500 bg-primary-50 px-2 py-1 rounded">${typeLabels[type]}</span>
            <div class="flex items-center gap-1">
                <button type="button" onclick="moveQuestion(${idx}, -1)" class="text-gray-400 hover:text-gray-600 p-1 text-xs" title="Move up">↑</button>
                <button type="button" onclick="moveQuestion(${idx}, 1)" class="text-gray-400 hover:text-gray-600 p-1 text-xs" title="Move down">↓</button>
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

function moveQuestion(idx, direction) {
    const container = document.getElementById('questions-container');
    const card = document.getElementById('question-card-' + idx);
    if (!card) return;

    const cards = Array.from(container.children);
    const currentIndex = cards.indexOf(card);
    const targetIndex = currentIndex + direction;

    if (targetIndex < 0 || targetIndex >= cards.length) return;

    if (direction === -1) {
        container.insertBefore(card, cards[targetIndex]);
    } else {
        container.insertBefore(card, cards[targetIndex].nextSibling);
    }
    reindexQuestions();
}

function reindexQuestions() {
    // Re-number the questions visually (not the form names — those use original index as key)
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// Load existing questions on page load
document.addEventListener('DOMContentLoaded', function () {
    if (window.existingQuestions && window.existingQuestions.length > 0) {
        window.existingQuestions.forEach(function (q) {
            addQuestion(q.type, q);
        });
    }
});

// Make functions globally available
window.addQuestion = addQuestion;
window.addOption = addOption;
window.removeOption = removeOption;
window.removeQuestion = removeQuestion;
window.moveQuestion = moveQuestion;
