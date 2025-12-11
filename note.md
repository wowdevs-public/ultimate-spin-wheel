phpcbf --standard=./phpcs.xml --report=summary .


    // Delay request to avoid multiple requests at once
    clearTimeout(window.widgetUpdateTimeout);
    window.widgetUpdateTimeout = setTimeout(() => {
    }, 500); // Delay request by 1000ms to batch updates


formRef.current.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));


===Spin Wheel probability fixed===
Key Fixes Applied:
1. Probability System Fixed:

Now correctly reads data-probability from ALL areas (both wins and losses)
Win areas with data-probability="0" will NEVER be selected
Win areas with higher probability percentages have a better chance
Loss areas share the remaining probability equally

2. Top Middle Indicator Fixed:

Changed the rotation calculation to align the winning segment's center with the top middle position (12 o'clock)
Formula: finalDeg = spinDegrees + (360 - targetAngle) ensures correct alignment

3. Simplified Logic:

Removed complex calculations
Clear probability normalization
Straightforward random selection based on cumulative probabilities

How the Probability Works:

If you have a 5$ prize with data-probability="100" and others with 0, the wheel will always land on the 5$ prize
To make users lose more often, set win probabilities low (e.g., 5-10%) and loss areas will automatically fill the rest
The wheel stops with the selected segment centered at the top middle marker

The wheel will now properly respect probabilities and stop at the correct position!
