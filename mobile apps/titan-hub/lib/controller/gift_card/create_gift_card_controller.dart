import '/utils/basic_screen_imports.dart';
import '../../../routes/routes.dart';
import '../../backend/local_storage/local_storage.dart';
import '../../backend/model/common/common_success_model.dart';
import '../../backend/model/gift_card/gift_details_model.dart';
import '../../backend/services/api_services.dart';
import '../../backend/services/gift_card_api_services.dart';
import 'gift_card_controller.dart';

class CreateGiftCardController extends GetxController {
  final controller = Get.put(GiftCardController());
  final receiverEmailController = TextEditingController();
  final phoneNumberController = TextEditingController();
  final fromNameController = TextEditingController();
  final quantityController = TextEditingController();
  final amountController = TextEditingController();

  RxString selectedWalletName = ''.obs;
  RxString selectedWalletCurrency = ''.obs;
  RxString selectedCountry = ''.obs;
  RxString selectedCountryCode = ''.obs;
  RxString mobileCode = ''.obs;
  RxString selectedAmount = ''.obs;
  RxInt selectedIndex = 0.obs;

  final List<CountryElement> countryList = [];
  final List<UserWallet> userWalletList = [];

  @override
  void onInit() {
    getGiftCardDetailsInfo();
    super.onInit();
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  final _isBuyLoading = false.obs;
  bool get isBuyLoading => _isBuyLoading.value;

  late GiftCardDetailsModel _giftCardDetailsModel;
  GiftCardDetailsModel get giftCardDetailsModel => _giftCardDetailsModel;

  Future<GiftCardDetailsModel> getGiftCardDetailsInfo() async {
    _isLoading.value = true;
    update();
    await GiftCardApiServices.getGiftCardDetailsApi(
            LocalStorages.getProductId())
        .then((value) {
      _giftCardDetailsModel = value!;
      selectedCountry.value =
          _giftCardDetailsModel.data.countries.first.name;
      selectedCountryCode.value =
          _giftCardDetailsModel.data.countries.first.iso2;
      mobileCode.value = _giftCardDetailsModel.data.countries.first.mobileCode;

      for (var element in _giftCardDetailsModel.data.countries) {
        countryList.add(
          CountryElement(
            name: element.name,
            mobileCode: element.mobileCode,
            currencyName: element.currencyName,
            currencyCode: element.currencyCode,
            currencySymbol: element.currencySymbol,
            iso2: element.iso2,
          ),
        );
      }

      /// User Wallet
      selectedWalletName.value =
          _giftCardDetailsModel.data.userWallet.first.name;
      selectedWalletCurrency.value =
          _giftCardDetailsModel.data.userWallet.first.currencyCode;
      for (var element in _giftCardDetailsModel.data.userWallet) {
        userWalletList.add(
          UserWallet(
            name: element.name,
            balance: element.balance,
            currencyCode: element.currencyCode,
            currencySymbol: element.currencySymbol,
            currencyType: element.currencyType,
            flag: element.flag,
            imagePath: element.imagePath,
            rate: element.rate,
          ),
        );
      }
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    _isLoading.value = false;
    update();
    return _giftCardDetailsModel;
  }

  late CommonSuccessModel _successModel;
  CommonSuccessModel get successModel => _successModel;

  Future<CommonSuccessModel> createGiftCardApi() async {
    _isLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'product_id': LocalStorages.getProductId(),
      'amount': giftCardDetailsModel
          .data.product.fixedRecipientDenominations[selectedIndex.value],
      'receiver_email': receiverEmailController.text,
      'receiver_country': selectedCountryCode.value,
      'receiver_phone_code': mobileCode.value,
      'receiver_phone': phoneNumberController.text,
      'from_name': fromNameController.text,
      'quantity': quantityController.text,
      'wallet_currency': selectedWalletCurrency.value,
    };

    await GiftCardApiServices.createGiftCardApi(body: inputBody).then((value) {
      _successModel = value!;
      controller.getMyCardInfoApi();
      Get.toNamed(Routes.giftCardScreen);
      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();

    return _successModel;
  }
}
