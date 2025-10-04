<template>
	<div class="character-avatar" :class="avatarClasses" :style="avatarStyles">
		<!-- Base Character Layer -->
		<div class="avatar-layer avatar-base" :style="getLayerStyle(0)">
			<img
				v-if="hasSprite('base')"
				:src="getSpriteUrl('base')"
				:alt="`${characterAge} character`"
				class="sprite-image" />
			<div v-else class="sprite-fallback base-fallback">
				{{ ageIcon }}
			</div>
		</div>

		<!-- Clothing Layer -->
		<div v-if="equipment.clothing" class="avatar-layer avatar-clothing" :style="getLayerStyle(10)">
			<img
				v-if="hasSprite('clothing')"
				:src="getSpriteUrl('clothing')"
				:alt="equipment.clothing"
				class="sprite-image" />
			<div v-else class="sprite-fallback">
				👕
			</div>
		</div>

		<!-- Accessory Layer -->
		<div v-if="equipment.accessory" class="avatar-layer avatar-accessory" :style="getLayerStyle(15)">
			<img
				v-if="hasSprite('accessory')"
				:src="getSpriteUrl('accessory')"
				:alt="equipment.accessory"
				class="sprite-image" />
			<div v-else class="sprite-fallback">
				📿
			</div>
		</div>

		<!-- Weapon Layer -->
		<div v-if="equipment.weapon" class="avatar-layer avatar-weapon" :style="getLayerStyle(20)">
			<img
				v-if="hasSprite('weapon')"
				:src="getSpriteUrl('weapon')"
				:alt="equipment.weapon"
				class="sprite-image" />
			<div v-else class="sprite-fallback">
				⚔️
			</div>
		</div>

		<!-- Headgear Layer -->
		<div v-if="equipment.headgear" class="avatar-layer avatar-headgear" :style="getLayerStyle(30)">
			<img
				v-if="hasSprite('headgear')"
				:src="getSpriteUrl('headgear')"
				:alt="equipment.headgear"
				class="sprite-image" />
			<div v-else class="sprite-fallback">
				👑
			</div>
		</div>

		<!-- Effects Layer -->
		<div class="avatar-layer avatar-effects" :style="getLayerStyle(40)">
			<!-- Badges -->
			<div v-for="badge in appearanceData.badges" :key="`badge-${badge}`" class="effect-badge">
				<img
					v-if="hasEffect('badge', badge)"
					:src="getEffectUrl('badge', badge)"
					:alt="`${badge} badge`"
					class="effect-image" />
			</div>

			<!-- Scars -->
			<div v-for="scar in appearanceData.scars" :key="`scar-${scar}`" class="effect-scar">
				<img
					v-if="hasEffect('scar', scar)"
					:src="getEffectUrl('scar', scar)"
					:alt="`${scar} scar`"
					class="effect-image" />
			</div>

			<!-- Aging Effects -->
			<div v-for="aging in appearanceData.aging_effects" :key="`aging-${aging}`" class="effect-aging">
				<img
					v-if="hasEffect('aging', aging)"
					:src="getEffectUrl('aging', aging)"
					:alt="`${aging} effect`"
					class="effect-image" />
			</div>

			<!-- Technology Markers -->
			<div v-for="tech in appearanceData.technology_markers" :key="`tech-${tech}`" class="effect-technology">
				<img
					v-if="hasEffect('technology', tech)"
					:src="getEffectUrl('technology', tech)"
					:alt="`${tech} marker`"
					class="effect-image" />
			</div>
		</div>

		<!-- Level Badge Overlay -->
		<div v-if="showLevel" class="avatar-level-badge" :style="`background: ${ageColor}`">
			{{ level }}
		</div>

		<!-- Age Indicator -->
		<div v-if="showAge" class="avatar-age-indicator" :style="`color: ${ageColor}`">
			{{ ageIcon }}
		</div>
	</div>
</template>

<script>
export default {
	name: 'CharacterAvatar',

	props: {
		characterAge: {
			type: String,
			default: 'stone'
		},
		level: {
			type: Number,
			default: 1
		},
		equipment: {
			type: Object,
			default: () => ({
				clothing: null,
				weapon: null,
				accessory: null,
				headgear: null
			})
		},
		appearanceData: {
			type: Object,
			default: () => ({
				scars: [],
				badges: [],
				aging_effects: [],
				technology_markers: []
			})
		},
		size: {
			type: String,
			default: 'medium', // small, medium, large, xlarge
			validator: (value) => ['small', 'medium', 'large', 'xlarge'].includes(value)
		},
		showLevel: {
			type: Boolean,
			default: true
		},
		showAge: {
			type: Boolean,
			default: false
		},
		animated: {
			type: Boolean,
			default: true
		}
	},

	computed: {
		ageConfig() {
			const ages = {
				stone: { icon: '🪨', color: '#8b7355', name: 'Stone Age' },
				bronze: { icon: '⚒️', color: '#cd7f32', name: 'Bronze Age' },
				iron: { icon: '⚔️', color: '#71706e', name: 'Iron Age' },
				medieval: { icon: '🏰', color: '#8b4513', name: 'Medieval Age' },
				renaissance: { icon: '🎨', color: '#daa520', name: 'Renaissance' },
				industrial: { icon: '⚙️', color: '#696969', name: 'Industrial Age' },
				modern: { icon: '💡', color: '#4169e1', name: 'Modern Age' },
				digital: { icon: '💻', color: '#00ced1', name: 'Digital Age' },
				space: { icon: '🚀', color: '#9370db', name: 'Space Age' }
			}
			return ages[this.characterAge] || ages.stone
		},

		ageIcon() {
			return this.ageConfig.icon
		},

		ageColor() {
			return this.ageConfig.color
		},

		avatarClasses() {
			return [
				`avatar-size-${this.size}`,
				`avatar-age-${this.characterAge}`,
				{
					'avatar-animated': this.animated,
					'avatar-has-equipment': this.hasEquipment
				}
			]
		},

		avatarStyles() {
			return {
				'--age-color': this.ageColor,
				'--age-color-light': `${this.ageColor}33`,
				'--age-color-dark': this.adjustColor(this.ageColor, -30)
			}
		},

		hasEquipment() {
			return Object.values(this.equipment).some(item => item !== null && item !== '')
		}
	},

	methods: {
		getLayerStyle(zIndex) {
			return {
				zIndex
			}
		},

		getSpriteUrl(equipmentType) {
			const baseUrl = window.OC.generateUrl('/apps/quest/img/characters')

			if (equipmentType === 'base') {
				// Use default male avatar if no custom base sprite is specified
				const baseSprite = this.equipment.baseSprite || 'male-default'
				const extension = baseSprite === 'male-default' ? 'png' : 'svg'
				return `${baseUrl}/base/${baseSprite}.${extension}`
			}

			const itemKey = this.equipment[equipmentType]
			if (!itemKey) return null

			return `${baseUrl}/${this.characterAge}/${equipmentType}/${itemKey}.svg`
		},

		hasSprite(equipmentType) {
			// Base sprite always available (male-default.png)
			if (equipmentType === 'base') {
				return true
			}
			// Other equipment types use fallback emojis for now
			return false
		},

		getEffectUrl(effectType, effectKey) {
			const baseUrl = window.OC.generateUrl('/apps/quest/img/characters')
			return `${baseUrl}/${this.characterAge}/effects/${effectType}/${effectKey}.svg`
		},

		hasEffect(effectType, effectKey) {
			// For now, return false to skip effects until sprites are created
			return false
		},

		adjustColor(color, amount) {
			// Simple color adjustment utility
			const clamp = (num) => Math.min(255, Math.max(0, num))

			const num = parseInt(color.replace('#', ''), 16)
			const r = clamp((num >> 16) + amount)
			const g = clamp(((num >> 8) & 0x00FF) + amount)
			const b = clamp((num & 0x0000FF) + amount)

			return `#${((r << 16) | (g << 8) | b).toString(16).padStart(6, '0')}`
		}
	}
}
</script>

<style scoped>
.character-avatar {
	position: relative;
	width: 100%;
	height: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(135deg, var(--age-color-light), transparent);
	border-radius: 50%;
	overflow: hidden;
}

/* Size Variations */
.avatar-size-small {
	width: 60px;
	height: 60px;
}

.avatar-size-medium {
	width: 120px;
	height: 120px;
}

.avatar-size-large {
	width: 200px;
	height: 200px;
}

.avatar-size-xlarge {
	width: 300px;
	height: 300px;
}

/* Avatar Layers */
.avatar-layer {
	position: absolute;
	width: 100%;
	height: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
	pointer-events: none;
}

.sprite-image {
	width: 100%;
	height: 100%;
	object-fit: contain;
}

.sprite-fallback {
	font-size: 4em;
	display: flex;
	align-items: center;
	justify-content: center;
	filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.avatar-size-small .sprite-fallback {
	font-size: 1.8em;
}

.avatar-size-medium .sprite-fallback {
	font-size: 3.5em;
}

.avatar-size-large .sprite-fallback {
	font-size: 6em;
}

.avatar-size-xlarge .sprite-fallback {
	font-size: 10em;
}

.base-fallback {
	opacity: 0.9;
}

/* Effects */
.avatar-effects {
	pointer-events: none;
}

.effect-badge,
.effect-scar,
.effect-aging,
.effect-technology {
	position: absolute;
	width: 30%;
	height: 30%;
}

.effect-badge {
	top: 5%;
	right: 5%;
}

.effect-scar {
	top: 30%;
	left: 10%;
}

.effect-aging {
	bottom: 20%;
	right: 15%;
}

.effect-technology {
	bottom: 5%;
	left: 5%;
}

.effect-image {
	width: 100%;
	height: 100%;
	object-fit: contain;
	filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.3));
}

/* Level Badge */
.avatar-level-badge {
	position: absolute;
	bottom: -5px;
	right: -5px;
	width: 32px;
	height: 32px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-weight: bold;
	font-size: 0.9em;
	color: white;
	border: 3px solid white;
	box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
	z-index: 50;
}

.avatar-size-small .avatar-level-badge {
	width: 20px;
	height: 20px;
	font-size: 0.65em;
	border-width: 2px;
	bottom: -3px;
	right: -3px;
}

.avatar-size-large .avatar-level-badge {
	width: 44px;
	height: 44px;
	font-size: 1.1em;
	bottom: -7px;
	right: -7px;
}

.avatar-size-xlarge .avatar-level-badge {
	width: 56px;
	height: 56px;
	font-size: 1.3em;
	bottom: -10px;
	right: -10px;
}

/* Age Indicator */
.avatar-age-indicator {
	position: absolute;
	top: 5px;
	left: 5px;
	font-size: 1.5em;
	filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.4));
	z-index: 50;
}

.avatar-size-small .avatar-age-indicator {
	font-size: 0.9em;
	top: 2px;
	left: 2px;
}

.avatar-size-large .avatar-age-indicator {
	font-size: 2em;
	top: 8px;
	left: 8px;
}

.avatar-size-xlarge .avatar-age-indicator {
	font-size: 2.5em;
	top: 12px;
	left: 12px;
}

/* Animations */
.avatar-animated .avatar-layer {
	transition: transform 0.3s ease, opacity 0.3s ease;
}

.avatar-animated:hover .avatar-layer {
	transform: scale(1.05);
}

.avatar-animated .effect-badge,
.avatar-animated .effect-technology {
	animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
	0%, 100% {
		transform: scale(1);
		opacity: 1;
	}
	50% {
		transform: scale(1.1);
		opacity: 0.8;
	}
}

/* Age-specific styling */
.avatar-age-stone {
	background: linear-gradient(135deg, #8b735522, transparent);
}

.avatar-age-bronze {
	background: linear-gradient(135deg, #cd7f3222, transparent);
}

.avatar-age-iron {
	background: linear-gradient(135deg, #71706e22, transparent);
}

.avatar-age-medieval {
	background: linear-gradient(135deg, #8b451322, transparent);
}

.avatar-age-renaissance {
	background: linear-gradient(135deg, #daa52022, transparent);
}

.avatar-age-industrial {
	background: linear-gradient(135deg, #69696922, transparent);
}

.avatar-age-modern {
	background: linear-gradient(135deg, #4169e122, transparent);
}

.avatar-age-digital {
	background: linear-gradient(135deg, #00ced122, transparent);
}

.avatar-age-space {
	background: linear-gradient(135deg, #9370db22, transparent);
}
</style>
