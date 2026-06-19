document.addEventListener('DOMContentLoaded', () => {
		if (typeof Nette === 'undefined'){
            console.error("Pozor: Nette.js není definováno! Náš validátor se nemá kam přidat.");    
            return;
        }

        // 1. Vytvoříme si samotnou funkci pro kontrolu IČO
        const icoValidatorFunction = function(elem, args, val) {
            console.log("Spouštím JS validaci IČO pro hodnotu:", val);
            
            // Vymažeme mezery, abychom pracovali s čistým číslem
            val = val.replace(/\s+/g, '');
            
            // Pokud je pole prázdné, necháme to na validaci "required"
            if (!val) return true;
            
            // Pokud to po smazání mezer nemá 8 číslic, rovnou vrátíme chybu
            if (!val.match(/^\d{8}$/)) return false;
            
            // Výpočet kontrolního součtu IČO
            var a = 0;
            for (var i = 0; i < 7; i++) {
                a += parseInt(val[i], 10) * (8 - i);
            }
            a = a % 11;
            var c = 11 - a;
            if (a === 1) c = 0;
            if (a === 0 || a === 10) c = 1;
            
            return c === parseInt(val[7], 10);
        };

        // Nette si teď vybere tu svoji transformovanou a stoprocentně ji najde.
        Nette.validators['AppUtilsIcoValidator_validateNette'] = icoValidatorFunction;

        const phoneInputs = document.querySelectorAll('input[data-phone-only]');

        phoneInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                // Povolíme pouze čísla, znak plus (+) a mezery
                // Všechno ostatní nahradíme prázdným řetězcem
                let cleaned = e.target.value.replace(/[^0-9+ ]/g, '');
                
                // Ochrana: Znak + smí být pouze na úplném začátku čísla
                if (cleaned.indexOf('+') > 0) {
                    // Pokud uživatel napsal + někam doprostřed, smažeme ho
                    cleaned = cleaned.substring(0, 1) + cleaned.substring(1).replace(/\+/g, '');
                }

                e.target.value = cleaned;
            });
        });

        const numberInputs = document.querySelectorAll('input[data-number-only]');

        numberInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                // Povolíme pouze čísla a mezery
                // Všechno ostatní nahradíme prázdným řetězcem
                let cleaned = e.target.value.replace(/[^0-9 ]/g, '');

                e.target.value = cleaned;
            });
        });

		// 1. Přepis funkce pro přidání chyby k políčku
		Nette.addError = function(element, message) {
			if (message) {
				element.classList.add('is-invalid');
				let errorDiv = element.parentNode.querySelector('.invalid-feedback');
				if (!errorDiv) {
					errorDiv = document.createElement('div');
					errorDiv.className = 'invalid-feedback';
					element.parentNode.appendChild(errorDiv);
				}
				errorDiv.innerText = message;
			}
		};

		// 2. Úplný přepis hlavní validační smyčky Nette
		Nette.validateForm = function(sender, onlyCheck) {
			const form = sender.form || sender;
			let isValid = true;
			let firstErrorElement = null;

			// Než začneme validovat, vyčistíme všechny předchozí chyby
			if (!onlyCheck) {
				form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
				form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
			}

			// Projdeme VŠECHNY prvky ve formuláři, jeden po druhém
			for (let i = 0; i < form.elements.length; i++) {
				const elem = form.elements[i];

				// Přeskočíme html tagy, které se nevalidují (např. fieldset)
				if (!(elem.nodeName.toLowerCase() in { input: 1, select: 1, textarea: 1 })) {
					continue;
				}

				// Zkontrolujeme konkrétní políčko
				// DŮLEŽITÉ: Tady se původní Nette zastavilo. My pokračujeme dál!
				if (!Nette.validateControl(elem, null, onlyCheck)) {
					isValid = false;
					
					// Zapamatujeme si úplně první chybné políčko, abychom na něj pak mohli skočit
					if (!firstErrorElement) {
						firstErrorElement = elem;
					}
				}
			}

			// Až když otestujeme úplně všechna pole, skočíme kurzorem na to první špatné
			if (!isValid && !onlyCheck && firstErrorElement) {
				firstErrorElement.focus();
			}

			return isValid;
		};

		// 3. Schování chyby, jakmile uživatel začne do políčka psát
		document.addEventListener('input', function(e) {
			if (e.target.classList && e.target.classList.contains('is-invalid')) {
				e.target.classList.remove('is-invalid');
				const errorDiv = e.target.parentNode.querySelector('.invalid-feedback');
				if (errorDiv) errorDiv.remove();
			}
		});

        const originalValidateRule = Nette.validateRule;
        Nette.validateRule = function(elem, op, arg, val) {
            // Vypíšeme si do konzole každé pravidlo, které Nette zkouší
            console.log("Nette zkouší ověřit pravidlo:", op, "na políčku:", elem.name);
            
            // Zjistíme, jestli Nette ten náš validátor pro dané 'op' vůbec vidí
            if (op.includes('IcoValidator')) {
                console.log("Hledaný klíč v Nette.validators:", op);
                console.log("Je validátor registrovaný?", typeof Nette.validators[op] === 'function');
                console.log("Seznam všech registrovaných validátorů:", Object.keys(Nette.validators));
            }
            
            return originalValidateRule.call(this, elem, op, arg, val);
        };

	});