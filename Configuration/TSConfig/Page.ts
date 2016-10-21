# Use templavoila's wizard instead the default create new page wizard

mod.web_list.newPageWiz.overrideWithExtension = templavoila
mod.web_txtemplavoilaM2.templatePath = templates,default/templates
mod.web_txtemplavoilaM1.enableDeleteIconForLocalElements = 0
mod.web_txtemplavoilaM1.enableContentAccessWarning = 1
mod.web_txtemplavoilaM1.enableLocalizationLinkForFCEs = 0
mod.web_txtemplavoilaM1.useLiveWorkspaceForReferenceListUpdates = 1
mod.web_txtemplavoilaM1.adminOnlyPageStructureInheritance = fallback

mod.wizards.newContentElement.wizardItems {
	fce.header = LLL:EXT:templavoila/Resources/Private/Language/PageModule/CreateContentController/locallang.xlf:fce
	fce.elements {
	}
	fce.show = *
}
