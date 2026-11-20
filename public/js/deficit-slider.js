const slider = document.querySelector('#deficit-slider');
const planData = document.querySelector('#deficit-plan');

if (slider && planData) {
    const plan = JSON.parse(planData.textContent);
    const kcalPerKg = 7700;
    const readout = document.querySelector('#deficit-readout');
    const weeks = document.querySelector('#plan-weeks');
    const calories = document.querySelector('#plan-calories');
    const eatNote = document.querySelector('#plan-eat-note');
    const pace = document.querySelector('#plan-pace');
    const todayTarget = document.querySelector('#today-target');
    const todayBalance = document.querySelector('#today-balance');
    const outlook = document.querySelector('#month-outlook');
    const weeklyInput = document.querySelector('input[name="weekly_loss_kg"]');

    const formatInt = (value) => Math.round(value).toLocaleString('en-US');
    const formatFixed = (value, digits) => value.toLocaleString('en-US', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    });

    const apply = () => {
        const deficit = Number(slider.value);
        const losing = plan.weightToLose > 0.05;
        const gaining = plan.weightToLose < -0.05;
        const weeklyLoss = deficit * 7 / kcalPerKg;
        const rawTarget = losing ? plan.tdee - deficit : plan.tdee + deficit;
        const floored = losing && rawTarget < plan.floor;
        let calorieTarget = Math.round(plan.tdee);

        if (losing) {
            calorieTarget = Math.round(Math.max(rawTarget, plan.floor));
        } else if (gaining) {
            calorieTarget = Math.round(rawTarget);
        }

        const effectiveWeekly = floored
            ? Math.max(0, plan.tdee - plan.floor) * 7 / kcalPerKg
            : Math.max(0, weeklyLoss);
        const weekCount = effectiveWeekly > 0 ? Math.abs(plan.weightToLose) / effectiveWeekly : 0;
        const atTarget = Math.abs(plan.weightToLose) <= 0.05;

        let timeline = 'holding your weight';
        let note = 'per day to hold your weight';
        let sentence = `A 0 kcal deficit holds your weight, so the eating target is ${formatInt(calorieTarget)} kcal.`;

        if (!atTarget && deficit > 0) {
            timeline = `about ${formatFixed(weekCount, 1)} weeks at ${formatFixed(effectiveWeekly, 2)} kg/week`;
            note = gaining ? 'per day to gain weight at that rate' : 'per day to lose weight at that rate';

            if (floored) {
                sentence = `A ${formatInt(deficit)} kcal deficit is capped so the target stays at ${formatInt(plan.floor)} kcal, about ${formatFixed(effectiveWeekly, 2)} kg a week.`;
            } else {
                const verb = gaining ? 'Gaining' : 'Losing';
                const gap = gaining ? 'surplus' : 'deficit';
                sentence = `${verb} ${formatFixed(weeklyLoss, 2)} kg a week is about a ${formatInt(deficit)} kcal daily ${gap}, so the eating target is ${formatInt(calorieTarget)} kcal.`;
            }
        } else if (atTarget) {
            sentence = `You are already at the target weight, so the eating target is ${formatInt(calorieTarget)} kcal.`;
        }

        if (readout) {
            readout.textContent = `${formatInt(deficit)} kcal`;
        }

        if (weeks) {
            weeks.textContent = timeline;
        }

        if (calories) {
            calories.textContent = `${formatInt(calorieTarget)} kcal`;
        }

        if (eatNote) {
            eatNote.textContent = note;
        }

        if (pace) {
            pace.textContent = sentence;
        }

        if (weeklyInput) {
            weeklyInput.value = weeklyLoss.toFixed(4);
        }

        if (todayTarget) {
            todayTarget.textContent = formatInt(calorieTarget);
        }

        if (todayBalance) {
            const remaining = calorieTarget - Number(todayBalance.dataset.eaten);
            todayBalance.textContent = remaining >= 0
                ? `${formatInt(remaining)} left`
                : `${formatInt(Math.abs(remaining))} over`;
        }

        if (outlook) {
            const actual = Math.round(plan.tdee - calorieTarget);
            const kind = actual < 0 ? 'surplus' : 'deficit';
            const nextWeight = plan.weightKg - (actual * 30 / kcalPerKg);
            outlook.textContent = `At ${formatFixed(plan.weightKg, 2)} kg the actual ${kind} is ${formatInt(Math.abs(actual))} kcal. In 30 days the scale would read about ${formatFixed(nextWeight, 2)} kg.`;
        }
    };

    slider.addEventListener('input', apply);
}
