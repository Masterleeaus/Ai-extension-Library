import 'package:qrpay/controller/categories/virtual_card/sudo_virtual_card/virtual_card_sudo_controller.dart';

import '../../../../backend/model/categories/virtual_card/virtual_card_sudo/identiti_type.dart';
import '../../../../backend/model/common/common_success_model.dart';
import '../../../../backend/services/api_services.dart';
import '../../../../backend/utils/custom_snackbar.dart';
import '../../../../backend/utils/logger.dart';
import '../../../../routes/routes.dart';
import '../../../../utils/basic_screen_imports.dart';

final log = logger(SudoAddFundController);

class SudoAddFundController extends GetxController {
  final virtualCardController = Get.put(VirtualSudoCardController());
  final identityNumberController = TextEditingController();
  final phoneController = TextEditingController();
  final birthdateController = TextEditingController();
  final List<String> totalAmount = [];
  Rxn<IdentityTypeModel> selectIdentityType = Rxn<IdentityTypeModel>();

  @override
  void onInit() {
    selectIdentityType.value = identityTypeList.first;
    super.onInit();
  }

  goToAddMoneyPreviewScreen() {
    Get.toNamed(Routes.addFundPreviewScreen);
  }

  goToAddMoneyCongratulationScreen() {
    Get.toNamed(Routes.addFundPreviewScreen);
  }

  List currencyList = ['USD', 'BDT'];
  List gatewayList = ['Paypal', 'Stripe'];

  // ---------------------------------------------------------------------------
  //                              Card Block Process
  // ---------------------------------------------------------------------------
  // -------------------------------Api Loading Indicator-----------------------
  //

  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;

  // -------------------------------Api Loading Indicator-----------------------

  late CommonSuccessModel _cardCreateData;
  CommonSuccessModel get cardCreateData => _cardCreateData;

  Future<CommonSuccessModel> cardCreateProcess() async {
    _isLoading.value = true;
    Map<String, dynamic> inputBody = {
      'date_of_birth': birthdateController.text,
      'identity_type': selectIdentityType.value!.value,
      'identity_number': identityNumberController.text,
      'phone_number': phoneController.text,
      'card_amount': virtualCardController.fundAmountController.text,
      'currency': virtualCardController.selectedCurrencyCode.value,
      'from_currency': virtualCardController.baseCurrency.value,
    };

    update();

    await ApiServices.sudoCreateCardApi(body: inputBody).then((value) {
      _cardCreateData = value!;

      CustomSnackBar.success(_cardCreateData.message.success.first);
      Get.offAllNamed(Routes.bottomNavBarScreen);

      update();
    }).catchError((onError) {
      log.e(onError);
    });

    _isLoading.value = false;
    update();
    return _cardCreateData;
  }

  final List<IdentityTypeModel> identityTypeList = [
    IdentityTypeModel(
      label: 'Bank Verification Number',
      value: 'BVN',
    ),
    IdentityTypeModel(
      label: 'National Identification Number',
      value: 'NIN',
    ),
    IdentityTypeModel(
      label: 'Taxpayer identification numbers',
      value: 'TIN',
    ),
  ];
}
