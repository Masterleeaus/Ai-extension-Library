import '../../language/english.dart';
import '../custom_assets/assets.gen.dart';
import '../routes/routes.dart';

class DrawerUtils {
  static List items = [
    {
      'title': Strings.giftCard,
      'icon': Assets.icon.giftLog,
      'route': Routes.giftCardScreen,
    },
    {
      'title': Strings.transactionLog,
      'icon': Assets.icon.tLog,
      'route': Routes.transactionLogScreen,
    },
    {
      'title': Strings.savedReceipients,
      'icon': Assets.icon.userRecipient,
      'route': Routes.saveRecipientScreen,
    },
    {
      'title': Strings.requestMoneyLog,
      'icon': Assets.icon.requestMoney,
      'route': Routes.requestMoneyLogScreen,
    },
    {
      'title': Strings.settings,
      'icon': Assets.icon.settings,
      'route': Routes.settingScreen,
    },
    {
      'title': Strings.helpCenter,
      'icon': Assets.icon.helpCenter,
      'route': Routes.helpCenterScreen,
    },
    {
      'title': Strings.privacyPolicy,
      'icon': Assets.icon.privacy,
      'route': Routes.privacyScreen,
    },
    {
      'title': Strings.aboutUs,
      'icon': Assets.icon.about,
      'route': Routes.aboutUsScreen,
    },
  ];
}
