# Vertical Extensions Mapping

**Strategy**: Rename and upgrade existing extensions instead of creating new ones

---

## E-commerce Vertical (7 extensions)

| Original Extension | New Name | Type | Upgrade Path |
|-------------------|----------|------|--------------|
| ChatbotEcommerce | EcommerceAssistant | Core | Upgrade to use TitanChannels, TitanTools |
| ProductPhotography | EcommerceProductPhotography | Media | Upgrade to use TitanStorage, TitanTools |
| MarketingBot | EcommerceMarketing | Marketing | Upgrade to use TitanChannels, TitanSkills |
| SocialMediaAgent | EcommerceSocialMedia | Social | Upgrade to use TitanChannels |
| SEOTool | EcommerceSEO | SEO | Upgrade to use TitanTools, TitanKnowledgeBase |
| UGCCreator | EcommerceUGC | Content | Upgrade to use TitanStorage, TitanDocs |
| ChatbotReview | EcommerceReviews | Community | Upgrade to use TitanMemory, TitanTools |

---

## Field Services & Home Services Vertical (8 extensions)

| Original Extension | New Name | Type | Upgrade Path |
|-------------------|----------|------|--------------|
| ChatbotBooking | FieldServicesBooking | Scheduling | Upgrade for service appointments |
| PhoneCallAgent | FieldServicesPhone | Phone | Upgrade for technician calls |
| ChatbotAgent | FieldServicesAgent | Automation | Upgrade for service workflows |
| TitanMapsIntelligence | FieldServicesRouting | Maps/Routing | Upgrade for technician routing |
| ChatbotVoice | FieldServicesVoice | Voice | Upgrade for voice support |
| AIPhotoshoot | FieldServicesDocumentation | Documentation | Upgrade for before/after photos |
| Canvas | FieldServicesVisuals | Visuals | Upgrade for work estimates |
| ChatbotCustomerTag | FieldServicesCustomerProfiles | CRM | Upgrade for customer data |

---

## Real Estate Vertical (8 extensions)

| Original Extension | New Name | Type | Upgrade Path |
|-------------------|----------|------|--------------|
| ChatbotBooking | RealEstateTours | Scheduling | Rename copy for tour scheduling |
| UrlToVideo | RealEstateVirtualTours | Media | Upgrade for property tours |
| ProductPhotography | RealEstatePhotography | Media | Rename copy for property photos |
| AiPresentation | RealEstatePresentation | Presentations | Upgrade for listing presentations |
| TitanMapsIntelligence | RealEstateMaps | Maps | Rename copy for property maps |
| ChatbotVoice | RealEstateVoice | Voice | Rename copy for inquiries |
| PhoneCallAgent | RealEstatePhone | Phone | Rename copy for agent calls |
| ChatbotAgent | RealEstateAgent | Automation | Upgrade for lead qualification |

---

## Fitness Vertical (6 extensions)

| Original Extension | New Name | Type | Upgrade Path |
|-------------------|----------|------|--------------|
| AiPersona | FitnessPersonalTrainer | AI Coach | Upgrade for personalization |
| AiMusic | FitnessPlaylist | Music | Upgrade for workout music |
| AiVideoPro | FitnessVideo | Video | Upgrade for workout videos |
| ContentManager | FitnessContent | Content | Upgrade for fitness content |
| AiCaptions | FitnessCaptions | Captions | Upgrade for video captions |
| AiPresentation | FitnessMilestones | Presentations | Upgrade for progress tracking |

---

## Shared Utilities (Not renamed)

These remain as-is and are used by multiple verticals via Titan services:

- Canvas (also used as FieldServicesVisuals, but kept for general use)
- ChatShare (collaboration across verticals)
- ChatSetting (preferences across verticals)
- AIWebChat (general chat interface)

---

## Extension Reuse Rationale

### ChatbotBooking
- **Original**: Generic appointment booking
- **E-commerce copy**: N/A (not needed)
- **Field Services version**: Service appointment scheduling
- **Real Estate version**: Property tour scheduling
- **Fitness version**: N/A (not needed)

**Strategy**: One codebase, deploy with different configurations/verticals

### ProductPhotography
- **Original**: Generic photo management
- **E-commerce version**: Product photography focus
- **Real Estate version**: Property photography focus
- **Fitness version**: N/A (not needed)

**Strategy**: Same codebase, different vertical templates

### TitanMapsIntelligence
- **Original**: Maps and intelligence
- **Field Services version**: Technician routing, service areas
- **Real Estate version**: Property location maps, neighborhood data
- **Fitness version**: N/A (not needed)

**Strategy**: One system, different vertical features

### ChatbotVoice
- **Original**: Voice conversations
- **Field Services version**: Voice support for services
- **Real Estate version**: Voice property inquiries
- **Fitness version**: Voice workout instructions (via FitnessVideo)

**Strategy**: Core implementation, vertical configurations

---

## GitHub Issues to Create

**Total Issues**: 29 vertical-specific upgrade issues

### E-commerce (7 issues)
- #[TBD] EcommerceAssistant: Upgrade ChatbotEcommerce
- #[TBD] EcommerceProductPhotography: Upgrade ProductPhotography
- #[TBD] EcommerceMarketing: Upgrade MarketingBot
- #[TBD] EcommerceSocialMedia: Upgrade SocialMediaAgent
- #[TBD] EcommerceSEO: Upgrade SEOTool
- #[TBD] EcommerceUGC: Upgrade UGCCreator
- #[TBD] EcommerceReviews: Upgrade ChatbotReview

### Field Services (8 issues)
- #[TBD] FieldServicesBooking: Upgrade ChatbotBooking
- #[TBD] FieldServicesPhone: Upgrade PhoneCallAgent
- #[TBD] FieldServicesAgent: Upgrade ChatbotAgent
- #[TBD] FieldServicesRouting: Upgrade TitanMapsIntelligence
- #[TBD] FieldServicesVoice: Upgrade ChatbotVoice
- #[TBD] FieldServicesDocumentation: Upgrade AIPhotoshoot
- #[TBD] FieldServicesVisuals: Upgrade Canvas
- #[TBD] FieldServicesCustomerProfiles: Upgrade ChatbotCustomerTag

### Real Estate (8 issues)
- #[TBD] RealEstateTours: Upgrade ChatbotBooking (copy)
- #[TBD] RealEstateVirtualTours: Upgrade UrlToVideo
- #[TBD] RealEstatePhotography: Upgrade ProductPhotography (copy)
- #[TBD] RealEstatePresentation: Upgrade AiPresentation
- #[TBD] RealEstateMaps: Upgrade TitanMapsIntelligence (copy)
- #[TBD] RealEstateVoice: Upgrade ChatbotVoice (copy)
- #[TBD] RealEstatePhone: Upgrade PhoneCallAgent (copy)
- #[TBD] RealEstateAgent: Upgrade ChatbotAgent (copy)

### Fitness (6 issues)
- #[TBD] FitnessPersonalTrainer: Upgrade AiPersona
- #[TBD] FitnessPlaylist: Upgrade AiMusic
- #[TBD] FitnessVideo: Upgrade AiVideoPro
- #[TBD] FitnessContent: Upgrade ContentManager
- #[TBD] FitnessCaptions: Upgrade AiCaptions
- #[TBD] FitnessMilestones: Upgrade AiPresentation (copy)

---

## Implementation Notes

1. **Code Reuse**: Each extension upgraded once, deployed multiple times with different configs
2. **Single Codebase**: Configuration files determine vertical-specific features
3. **Shared Utilities**: TitanChannels, TitanSkills, etc. handle all common functionality
4. **Quick Migration**: Most extensions need only configuration changes
5. **Titan Integration**: All use TitanChannels, TitanMemory, TitanTools, TitanStorage as appropriate

---

**Total Existing Extensions Used**: 29 (mapped to 29 vertical variants)  
**Extensions Not Used**: 49 (Creative AI, Model Providers, etc. - handle separately)
