const nameInput = document.querySelector('#food-name');
const results = document.querySelector('#food-results');
const caloriesInput = document.querySelector('#food-calories');
const proteinInput = document.querySelector('#food-protein');
const carbsInput = document.querySelector('#food-carbs');
const fatInput = document.querySelector('#food-fat');
const serving = document.querySelector('#food-serving');

if (nameInput && results) {
    let timer = null;

    const hide = () => {
        results.hidden = true;
        results.replaceChildren();
    };

    const fill = (food) => {
        nameInput.value = food.name;
        caloriesInput.value = food.calories;
        proteinInput.value = food.protein;
        carbsInput.value = food.carbs;
        fatInput.value = food.fat;
        serving.textContent = food.serving ? `${food.serving} · ${food.source}` : food.source;
        hide();
    };

    nameInput.addEventListener('input', () => {
        clearTimeout(timer);
        const query = nameInput.value.trim();

        if (query.length < 2) {
            hide();
            return;
        }

        timer = setTimeout(async () => {
            const response = await fetch(`/foods/search?q=${encodeURIComponent(query)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                hide();
                return;
            }

            const foods = await response.json();
            results.replaceChildren();

            if (foods.length === 0) {
                const item = document.createElement('li');
                const note = document.createElement('small');
                note.textContent = 'No foods found. Try a simpler name, like oats or chicken.';
                item.append(note);
                results.append(item);
                results.hidden = false;
                return;
            }

            foods.forEach((food) => {
                const item = document.createElement('li');
                const button = document.createElement('button');
                button.type = 'button';

                const title = document.createElement('span');
                title.textContent = food.name;

                const detail = document.createElement('small');
                detail.textContent = `${food.calories} kcal · P ${food.protein} g · C ${food.carbs} g · F ${food.fat} g · ${food.serving} · ${food.source}`;

                button.append(title, detail);
                button.addEventListener('click', () => fill(food));
                item.append(button);
                results.append(item);
            });

            results.hidden = foods.length === 0;
        }, 250);
    });

    document.addEventListener('click', (event) => {
        if (!nameInput.parentElement.contains(event.target)) {
            hide();
        }
    });
}
